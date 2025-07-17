<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\RequestContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RequestLoggerMiddleware
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        return $next($request);
    }

    public function terminate(Request $request, SymfonyResponse $response): void
    {
        // `request_id` and `user_id` are already added to log context by \App\Services\ContentLengthMiddleware

        Log::info(
            "{$request->getMethod()} {$request->getPathInfo()} {$response->getStatusCode()}",
            [
                'header_bytes' => app(RequestContextService::class)->getHeaderBytes($response),
                'body_bytes' => app(RequestContextService::class)->getBodyBytes($response),
                'duration' => app(RequestContextService::class)->getDurationMs(),
                'cpu_time' => app(RequestContextService::class)->getCpuTimeMs(),
                'db_durations' => app(RequestContextService::class)->getDbDurationsMs(),
                'memory' => $this->formateBytes(memory_get_peak_usage(true)),
                'pid' => getmypid(),
                'realpath' => $this->formateBytes(realpath_cache_size()),

                // Debugging IP address and TrustProxies
                // 'client_ip' => $request->ip(),                                       // First value of ->ips(). Should be the client's IP
                // 'ips' => $request->ips(),                                            // Trusted values of X-Forwarded-For header plus REMOTE_ADDR
                // 'remote_addr' => $request->server('REMOTE_ADDR'),               // IP of TCP connection to Nginx. Should be Ingress 10.42.0.0/16
                // 'is_secure' => $request->isSecure(),                                 // Is HTTPS and Laravel trusts proxies
                // 'scheme' => $request->getScheme(),
                // 'x_forwarded_for' => $request->header('X-Forwarded-For'),       // Header
                // 'x_real_ip' => $request->header('X-Real-IP'),                   // Header
                // 'x_forwarded_proto' => $request->header('X-Forwarded-Proto'),   // Header

                // 'request' => [
                //     'headers' => $this->redactHeaders($request->headers->all()),
                //     'query'   => $request->query(),
                // ],
                // 'response' => [
                //     'size' => mb_strlen($response->getContent()),
                //     'content_type' => $response->headers->get('Content-Type'),
                // ],
            ]
        );
    }

    /**
     * Don't log sensitive information in headers
     */
    // protected function redactHeaders(array $headers): array
    // {
    //     $headersToRedact = ['authorization', 'cookie'];

    //     foreach ($headers as $key => $value) {
    //         if (in_array(strtolower($key), $headersToRedact)) {
    //             $headers[$key] = ['REDACTED'];
    //         }
    //     }

    //     return $headers;
    // }

    private function formateBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
