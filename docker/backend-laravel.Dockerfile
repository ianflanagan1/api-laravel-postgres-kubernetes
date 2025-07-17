FROM php:8.4-fpm-bookworm AS builder
# Includes: curl, dom, mbstring, mysqlnd, pdo, sqlite3, tokenizer, xml

RUN mkdir -p \
        /app \
        /var/run/php \
    && rm -rf /var/www \
    && groupmod -g 65532 www-data \
    && usermod -u 65532 -g 65532 www-data \
    && chown www-data:www-data /var/run/php \
    && chmod 0700 /var/run/php

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        # libcurl4-openssl-dev \
        # libonig-dev \
        # libpq-dev \
        # libssl-dev \
        # libxml2-dev \
        # libzip-dev \
        # unzip \
        libonig-dev \
        libpq-dev \
        libssl-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        opcache \
        pdo_pgsql \
        pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# libonig-dev   for mbstring
# libpq-dev     for pdo_pgsql, pgsql
# libssl-dev    for OpenSSL
# libxml2-dev   for soap, xml, dom, etc.
# libzip-dev    for zip extension
# unzip         for composer install

# Analysis tools
RUN apt-get install -y --no-install-recommends \
        procps \
        smem \
        time

RUN apt-get autoremove -y \
    && apt-get clean \
    && rm -rf \
        /var/lib/apt/lists/* \
        /var/tmp/* \
        /tmp/*

COPY --from=docker.io/library/composer:latest /usr/bin/composer /usr/bin/composer

#################################################################################

FROM builder AS dev

# This container will communicate with Nginx over Unix socket instead of TCP.
# So here we delete the default config file, and add 'www-data' user to the
# 'nginx' group, so it can create a socket with permissions allowing nginx.
# Elsewhere (Docker Composer/Kubernetes) we add a replacement config file and 
# share a volume between the PHP-FPM and Nginx containers
# RUN rm /usr/local/etc/php-fpm.d/zz-docker.conf \
#     && groupadd --gid 65532 nginx \
#     && usermod -aG nginx www-data \
#     && mkdir -p /var/run/php \
#     && chown www-data:nginx /var/run/php \
#     && chmod 750 /var/run/php

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Output directory for Xdebug cachegrind files
RUN mkdir /tmp/profiles \
    && chown -R www-data:www-data /tmp/profiles

# COPY ./config/debug/xdebug.ini ${PHP_INI_DIR}/conf.d/xdebug.ini   // alternative to bind mount

USER www-data
ENTRYPOINT ["/usr/local/sbin/php-fpm"]
CMD []

#################################################################################

FROM dev AS combined-php-fpm-nginx

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        supervisor \
        nginx \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf \
        /var/lib/apt/lists/* \
        /var/tmp/* \
        /tmp/*

RUN mkdir -p /var/log/supervisord

RUN useradd -r -g nginx -u 65532 nginx
RUN usermod -a -G www-data nginx

USER www-data
ENTRYPOINT ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf", "-n"]
CMD []
EXPOSE 8080/tcp

#################################################################################

# For: prod target only
# Install composer dependencies from the lock file (dev will bind mount local vendor/ directory)

FROM builder AS builder-prod

USER root

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-dev --no-autoloader --prefer-dist
COPY . .
    # see.dockerignore

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction --strict-psr --strict-ambiguous

# Delete useless directories/files from vendor/ directory
RUN find vendor/ \
        -type d \( \
            -iname test \
            -o -iname tests \
            -o -iname doc \
            -o -iname docs \
            -o -iname examples \
        \) -prune -exec rm -rf '{}' + \
    && find vendor/ \
        -type f \( \
            -iname "*.md" \
            -o -iname "*.markdown" \
            -o -iname "*.editorconfig" \
            -o -iname ".gitignore" \
            -o -iname ".gitattributes" \
            # -o -iname "*.xml" \
            # -o -iname "*.txt" \
            # -o -iname "*.yml" \
            # -o -iname "*.yaml" \
            # -o -iname "*.rst" \
            # -o -iname "*.dist" \
            # -o -iname "*.ini" \
        \) -delete

#################################################################################

# For: prod and dev targets
# In a fresh base, install/copy only the extensions required at runtime

FROM php:8.4-fpm-bookworm AS minimal-base

ENV HOME=/app \
    LC_ALL=C.UTF-8 \
    LANG=C.UTF-8

RUN mkdir -p \
        /app \
        /var/run/php \
    && rm -rf /var/www \
    && groupmod -g 65532 www-data \
    && usermod -u 65532 -g 65532 www-data \
    && chown www-data:www-data /var/run/php \
    && chmod 0700 /var/run/php

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfcgi-bin \
        libpq5 \
        libzip4

# libfcgi-bin   for PHP-FPM healthcheck
# libpq5        for pdo_pgsql, pgsql
# libzip4       for zip extension

# todo: remove later. ps, top, free, smem, time
RUN apt-get install -y --no-install-recommends \
        procps \
        smem \
        time

RUN apt-get autoremove -y \
    && apt-get clean \
    && rm -rf \
        /var/lib/apt/lists/* \
        /var/tmp/* \
        /tmp/*

# This container will communicate with Nginx over Unix socket instead of TCP.
# So here we delete the default config file, and add 'www-data' user to the
# 'nginx' group, so it can create a socket with permissions allowing nginx.
# Elsewhere (Docker Composer/Kubernetes) we add a replacement config file and 
# share a volume between the PHP-FPM and Nginx containers
# RUN rm /usr/local/etc/php-fpm.d/zz-docker.conf \
#     && groupadd --gid 65532 nginx \
#     && usermod -aG nginx www-data \
#     && mkdir -p /var/run/php \
#     && chown www-data:nginx /var/run/php \
#     && chmod 750 /var/run/php

# PHP-FPM healthcheck, e.g. for Liveness check
RUN curl -sSL https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
        -o /usr/local/bin/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Copy extensions from `Builder` target
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=builder /usr/local/bin/docker-php-ext-* /usr/local/bin/

#################################################################################

FROM minimal-base AS minimal-dev

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Output directory for Xdebug cachegrind files
RUN mkdir /tmp/profiles \
    && chown -R www-data:www-data /tmp/profiles

# COPY ./config/debug/xdebug.ini ${PHP_INI_DIR}/conf.d/xdebug.ini   // alternative to bind mount

USER www-data
ENTRYPOINT ["/usr/local/sbin/php-fpm"]
CMD []

#################################################################################

FROM minimal-base AS prod

COPY --from=builder-prod /app /app

RUN php artisan event:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && rm composer.json composer.lock
    # php artisan config:cache requires .env variables

# No longer needed after caching
RUN rm -rf \
        /app/routes \
        /app/resources/views

# Rename the directories so that in Kubernetes volumes can be mounted in their places
# Then at startup, copy the contents back in to the mounted volume
RUN mv /app/bootstrap/cache /app/bootstrap/cache-temp \
    && mv /app/storage/app /app/storage/app-temp

# Render Laravel health check view
# RUN php -S localhost:8000 -t public > /dev/null 2>&1 & \
#     SERVER_PID=$! \
#     && sleep 1 \
#     && curl -s http://localhost:8000/laravel-readiness > /dev/null \
#     && kill $SERVER_PID

# Reduce file permissions to the minimum required
RUN chown -R www-data:www-data /app \
    && chmod -R 0440 /app \
    && find /app -type d -exec chmod 0550 {} +
    # && chmod 0770 /app/bootstrap/cache \
    # && chmod 0770 /app/storage \
    # && install -o www-data -g www-data -m 0660 /dev/null /app/storage/logs/laravel.log \
    # && install -o www-data -g www-data -m 0660 /dev/null /app/database/database.sqlite \
    # && php artisan storage:link

USER www-data
ENTRYPOINT ["/usr/local/sbin/php-fpm"]
CMD []