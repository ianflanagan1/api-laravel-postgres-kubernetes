<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CacheControlMiddleware
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        $content = $response->getContent();
        if ($content === false) {
            $content = '';
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }
}
