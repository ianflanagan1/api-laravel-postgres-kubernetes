<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\RequestContextService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Middleware to inject standardized metadata into all JSON API responses.
 */
class InjectMetaMiddleware
{
    public function __construct(protected RequestContextService $requestContextService) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $original = (array) $response->getData(true);

            $original['meta'] = [
                'request_id' => $this->requestContextService->getRequestId(),
                'timestamp' => now()->toIso8601String(),
                'duration' => $this->requestContextService->getDurationMs(),
            ];

            $response->setData($original);
        }

        return $response;
    }
}
