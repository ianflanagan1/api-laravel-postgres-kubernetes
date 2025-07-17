<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Middleware to set the Content-Length HTTP header on responses.
 *
 * Nginx and Apache need an accurate Content-Length header to respect the `gzip_min_length`
 * and `brotli_min_length` configuration settings. Without this header, Nginx/Apache will compress
 * all responses regardless of size.
 *
 * @see https://nginx.org/en/docs/http/ngx_http_gzip_module.html#gzip_min_length
 */
class ContentLengthMiddleware
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        $body = $response->getContent();
        if ($body === false) {
            $body = '';
        }

        // Ensure php.ini:mbstring.func_overload=0, otherwise use: mb_strlen($body, '8bit')
        $response->headers->set('Content-Length', (string) strlen($body));

        return $response;
    }
}
