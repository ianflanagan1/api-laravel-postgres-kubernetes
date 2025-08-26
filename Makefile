# constants
LARAVEL_DIRECTORY									:= backend-laravel
VUE_DIRECTORY											:= frontend-vue

IMAGE_NAMESPACE 									:= ianflanagan1

BACKEND_LARAVEL_DOCKERFILE				:= backend-laravel.Dockerfile
BACKEND_LARAVEL_PROD_IMAGE 				:= $(IMAGE_NAMESPACE)/wh-backend-laravel-prod
BACKEND_LARAVEL_DEV_IMAGE 				:= $(IMAGE_NAMESPACE)/wh-backend-laravel-dev

BACKEND_NGINX_DOCKERFILE					:= backend-nginx.Dockerfile
BACKEND_NGINX_PROD_IMAGE					:= $(IMAGE_NAMESPACE)/wh-backend-nginx-prod
BACKEND_NGINX_DEV_IMAGE						:= $(IMAGE_NAMESPACE)/wh-backend-nginx-dev

FRONTEND_DOCKERFILE								:= frontend.Dockerfile
FRONTEND_PROD_IMAGE 							:= $(IMAGE_NAMESPACE)/wh-frontend-nginx-prod
FRONTEND_DEV_IMAGE 								:= $(IMAGE_NAMESPACE)/wh-frontend-nginx-dev

BACKEND_COMPOSE_PROJECT						:= wh
COMPOSE_BACKEND_NGINX_CONTAINER		:= $(BACKEND_COMPOSE_PROJECT)-backend-nginx-1
COMPOSE_LARAVEL_CONTAINER					:= $(BACKEND_COMPOSE_PROJECT)-laravel-1
COMPOSE_POSTGRES_CONTAINER				:= $(BACKEND_COMPOSE_PROJECT)-postgres-1
COMPOSE_REDIS_CONTAINER						:= $(BACKEND_COMPOSE_PROJECT)-redis-1
COMPOSE_FRONTEND_NGINX_CONTAINER	:= $(BACKEND_COMPOSE_PROJECT)-frontend-nginx-1

PRODUCTION_BACKEND_DOMAIN					:= https://wh-api.ianf.dev
PRODUCTION_FRONTEND_DOMAIN				:= https://wh-vue.ianf.dev

# inputs
A ?=
NC ?=
ROOT ?=

# --no-cache flag
ifdef NC
	NO_CACHE_STRING = --no-cache
else
	NO_CACHE_STRING =
endif

# --user=root:root flag
ifdef ROOT
	ROOT_USER_STRING = --user root:root
else
	ROOT_USER_STRING =
endif

arg-check:
	@if [ -z "$(A)" ]; then \
		echo "Error: Needs argument"; \
		exit 1; \
	fi

up:
	docker compose -f docker/compose.yaml --env-file ./$(LARAVEL_DIRECTORY)/.env up --build

up-detach:
	docker compose -f docker/compose.yaml --env-file ./$(LARAVEL_DIRECTORY)/.env up --build --detach

down:
	docker compose -f docker/compose.yaml down

down-delete:
	docker compose -f docker/compose.yaml down --volumes --remove-orphans


##### LARAVEL CONTAINER #####

exec-laravel: arg-check
	docker exec -it \
		--user=www-data:$(shell id -g) \
		$(COMPOSE_LARAVEL_CONTAINER) \
			sh -c "umask 0002 && $(A)"

shell-laravel: A=/bin/sh
shell-laravel: exec-laravel

define composer-cmd
	docker exec -it \
		--user=www-data:$(shell id -g) \
		-e XDEBUG_MODE=off \
		-e COMPOSER_CACHE_DIR=/tmp/composer-cache \
		$(COMPOSE_LARAVEL_CONTAINER) \
			sh -c "umask 0002 && /usr/bin/composer $(1) --no-interaction --no-progress"
endef

composer-install:
	$(call composer-cmd,install)
composer-update:
	$(call composer-cmd,update)
composer-require: arg-check
	$(call composer-cmd,require $(A))
composer-remove: arg-check
	$(call composer-cmd,remove $(A))

migrate:
	$(call exec-laravel,php artisan migrate)
migrate-fresh:
	$(call exec-laravel,php artisan migrate:fresh)
seed:
	$(call exec-laravel,php artisan db:seed)

##### OTHER CONTAINERS #####

define exec-cmd
	docker exec -it $(ROOT_USER_STRING) $(1)
endef

exec-backend-nginx: arg-check
	$(call exec-cmd,$(COMPOSE_BACKEND_NGINX_CONTAINER) $(A))
exec-postgres: arg-check
	$(call exec-cmd,$(COMPOSE_POSTGRES_CONTAINER) $(A))
exec-redis: arg-check
	$(call exec-cmd,$(COMPOSE_REDIS_CONTAINER) $(A))
exec-frontend-nginx: arg-check
	$(call exec-cmd,$(COMPOSE_FRONTEND_NGINX_CONTAINER) $(A))

shell-backend-nginx: A=/bin/sh
shell-backend-nginx: exec-backend-nginx
shell-postgres: A=/bin/sh
shell-postgres: exec-postgres
shell-redis: A=/bin/sh
shell-redis: exec-redis
shell-frontend-nginx: A=/bin/sh
shell-frontend-nginx: exec-frontend-BACKEND_NGINX_DEV_IMAGE

##### PHP #####

phpstan:
	cd $(LARAVEL_DIRECTORY) && \
		php ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G --configuration=phpstan.neon $(filter-out $@,$(MAKECMDGOALS))

pint:
	cd $(LARAVEL_DIRECTORY) && \
  	php ./vendor/bin/pint -v --config=pint.json
	cd $(LARAVEL_DIRECTORY) && \
  	php ./vendor/bin/pint -v --config=pint-tests.json tests/

test:
	docker exec \
		--user=www-data:$(shell id -g) \
		$(COMPOSE_LARAVEL_CONTAINER) \
			sh -c 'umask 0002; \
				/usr/local/bin/run_php_tests.sh true 65 exclude EndToEnd-Backend,EndToEnd-Frontend'
test-e2e:
	docker exec \
		--user=www-data:$(shell id -g) \
		-e E2E_TEST_BASE_URL_BACKEND=$(PRODUCTION_BACKEND_DOMAIN) \
		-e E2E_TEST_BASE_URL_FRONTEND=$(PRODUCTrun_pION_FRONTEND_DOMAIN) \
		$(COMPOSE_LARAVEL_CONTAINER) \
			sh -c 'umask 0002; \
				/usr/local/bin/run_php_tests.sh false "" include EndToEnd-Backend,EndToEnd-Frontend'
test-e2e-backend:
	docker exec \
		--user=www-data:$(shell id -g) \
		-e E2E_TEST_BASE_URL_BACKEND=$(PRODUCTION_BACKEND_DOMAIN) \
		$(COMPOSE_LARAVEL_CONTAINER) \
			sh -c 'umask 0002; \
				 /usr/local/bin/run_php_tests.sh false "" include EndToEnd-Backend'
test-e2e-frontend:
	docker exec \
		--user=www-data:$(shell id -g) \
		-e E2E_TEST_BASE_URL_FRONTEND=$(PRODUCTION_FRONTEND_DOMAIN) \
		$(COMPOSE_LARAVEL_CONTAINER) \
			sh -c 'umask 0002; /
				/usr/local/bin/run_php_tests.sh false "" include EndToEnd-Frontend'

###### BUILD ######

build-laravel-prod:
	docker build --progress=plain $(NO_CACHE_STRING) -f docker/$(BACKEND_LARAVEL_DOCKERFILE) --target=prod -t $(BACKEND_LARAVEL_PROD_IMAGE) $(LARAVEL_DIRECTORY)/
build-backend-nginx-prod:
	docker build --progress=plain $(NO_CACHE_STRING) -f docker/$(BACKEND_NGINX_DOCKERFILE) --target prod -t $(BACKEND_NGINX_PROD_IMAGE) $(LARAVEL_DIRECTORY)/
build-frontend-nginx-prod:
	cd $(VUE_DIRECTORY) && \
		npm run build
	docker build --progress=plain $(NO_CACHE_STRING) -f docker/$(FRONTEND_DOCKERFILE) --target=prod -t $(FRONTEND_PROD_IMAGE) ${VUE_DIRECTORY}/

###### KUBERNETES ######

use-kind:
	kubectl config use-context kind-kind
use-home-k3s:
	kubectl config use-context home-k3s

update-secrets-configmaps:
	kubectl create secret generic laravel \
		--namespace laravel \
		--from-env-file=$(LARAVEL_DIRECTORY)/.env.production \
		--dry-run=client -o yaml | \
			kubectl apply -f - --namespace laravel
	kubectl create secret generic redis \
		--namespace redis \
		--from-literal=REDIS_PASSWORD="$$(grep '^REDIS_PASSWORD=' "$(LARAVEL_DIRECTORY)/.env.production" | awk -F'=' '{print $$2}')" \
		--dry-run=client -o yaml | \
			kubectl apply -f - --namespace redis
	kubectl create secret generic postgres \
  	--namespace laravel \
	  --from-literal=POSTGRES_USER="$$(grep '^POSTGRES_USER=' "$(LARAVEL_DIRECTORY)/.env.production" | awk -F'=' '{print $$2}')" \
	  --from-literal=POSTGRES_PASSWORD="$$(grep '^POSTGRES_PASSWORD=' "$(LARAVEL_DIRECTORY)/.env.production" | awk -F'=' '{print $$2}')" \
	  --from-literal=POSTGRES_DB="$$(grep '^POSTGRES_DB=' "$(LARAVEL_DIRECTORY)/.env.production" | awk -F'=' '{print $$2}')" \
	  --from-literal=REPLICATION_USER="$$(grep '^REPLICATION_USER=' "$(LARAVEL_DIRECTORY)/.env.production" | awk -F'=' '{print $$2}')" \
	  --from-literal=REPLICATION_PASSWORD="$$(grep '^REPLICATION_PASSWORD=' "$(LARAVEL_DIRECTORY)/.env.production" | awk -F'=' '{print $$2}')" \
		--dry-run=client -o yaml | \
			kubectl apply -f - --namespace laravel
	kubectl create configmap php-config \
		--namespace laravel \
		--from-file=config/prod/backend/php/php.ini -o yaml \
		--dry-run=client | \
			kubectl apply -f - --namespace laravel
	kubectl create configmap php-fpm-config \
		--namespace laravel \
		--from-file=config/prod/backend/php-fpm/php-fpm.conf -o yaml \
		--dry-run=client | \
			kubectl apply -f - --namespace laravel
	kubectl create configmap nginx-config \
		--namespace laravel \
		--from-file=config/prod/backend/nginx/nginx.conf -o yaml \
		--dry-run=client | \
			kubectl apply -f - --namespace laravel
	kubectl create configmap nginx-config \
		--namespace frontend \
		--from-file=config/prod/frontend/nginx/nginx.conf -o yaml \
		--dry-run=client | \
			kubectl apply -f - --namespace frontend
	

push-laravel-prod: build-laravel-prod update-secrets-configmaps
	docker push $(BACKEND_LARAVEL_PROD_IMAGE)
	kubectl -n laravel delete pods -l app=laravel
	kubectl -n laravel get pods
# push-backend-nginx-dev: build-backend-nginx-dev update-secrets-configmaps
# 	docker push $(BACKEND_NGINX_DEV_IMAGE)
# 	kubectl -n laravel delete pods -l app=laravel
# 	kubectl -n laravel get pods
push-backend-nginx-prod: build-backend-nginx-prod update-secrets-configmaps
	docker push $(BACKEND_NGINX_PROD_IMAGE)
	kubectl -n laravel delete pods -l app=laravel
	kubectl -n laravel get pods
# push-frontend-nginx-dev: build-frontend-nginx-dev update-secrets-configmaps
# 	docker push $(FRONTEND_DEV_IMAGE)
# 	kubectl -n frontend delete pods -l app=frontend
# 	kubectl -n frontend get pods
push-frontend-nginx-prod: build-frontend-nginx-prod update-secrets-configmaps
	docker push $(FRONTEND_PROD_IMAGE)
	kubectl -n frontend delete pods -l app=frontend
	kubectl -n frontend get pods

pf-prometheus:
	kubectl -n monitoring port-forward service/prometheus-kube-prometheus-prometheus 9090:9090
pf-laravel-internal:
	kubectl -n laravel port-forward service/laravel 9081:8081