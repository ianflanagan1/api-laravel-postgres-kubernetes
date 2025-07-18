<?php

declare(strict_types=1);

use App\Exceptions\Handlers\ApiExceptionHandler;
use App\Http\Middleware\CacheControlMiddleware;
use App\Http\Middleware\ContentLengthMiddleware;
use App\Http\Middleware\InjectMetaMiddleware;
use App\Http\Middleware\RequestLoggerMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Middleware\ValidatePathEncoding;
use Illuminate\Http\Middleware\ValidatePostSize;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware
            ->trustProxies(at: ['10.42.0.0/16'])  // Ingress Controller
            ->use([])
            ->group('api', [
                RequestLoggerMiddleware::class,     // Keep first to record request duration last. Log the request after response sent
                ValidatePathEncoding::class,        // Check URL is UTF-8
                InvokeDeferredCallbacks::class,     // Execute defer() calls
                TrustProxies::class,                // Trust Ingress Controller IP above
                HandleCors::class,                  // Access-Control-Allow-Origin, Access-Control-Allow-Credentials headers
                ValidatePostSize::class,            // Throw `PostTooLargeException` when `post_max_size` exceeded
                TrimStrings::class,
                ConvertEmptyStringsToNull::class,
                CacheControlMiddleware::class,      // Adds Cache-Control header
                ContentLengthMiddleware::class,     // Keep above all middleware that modifies response body. Adds Content-Length header

                // Can modify response body
                InjectMetaMiddleware::class,        // Add ['meta'] section to JsonResponse
            ])
            ->group('web', [
                ValidatePathEncoding::class,        // Check URL is UTF-8
            ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        (new ApiExceptionHandler)->register($exceptions);
    })->create();
