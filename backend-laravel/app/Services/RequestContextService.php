<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RequestContextService
{
    protected float $startTime;
    /**
     * @var array<string, int>|false
     */
    protected array|false $startUsage;

    /**
     * @var array<float>
     */
    protected array $dbDurations = [];

    protected string $requestId;

    public function __construct(Request $request)
    {
        $this->startTime = microtime(true);
        $this->startUsage = getrusage();
        $this->requestId = $request->header('X-Request-ID') ?? '';
        Log::withContext(['request_id' => $this->requestId]);
    }

    public function addDbDuration(float $duration): void
    {
        $this->dbDurations[] = $duration;
    }

    public function getDbDurationsMs(): string
    {
        return implode(',', $this->dbDurations);
    }

    /**
     * Get the request ID.
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Calculate and get the "wall-clock" duration of the request in milliseconds.
     */
    public function getDurationMs(): int
    {
        return (int) round((microtime(true) - $this->startTime) * 1000);
    }


    /**
     * Calculate and get the "CPU-time" duration of the request in milliseconds.
     *
     *
     * ru_utime.tv_sec:     Whole seconds spent in 'user mode' (e.g. PHP interpreter work) since the PHP-FPM worker process started
     * ru_utime.tv_usec:    Microseconds on top of that last whole second
     * ru_stime.tv_sec      Whole seconds the kernel spent working on behalf of this process (e.g I/O, system calls - Excluding time spent waiting)
     *                      since the PHP-FPM worker process started
     * ru_stime.tv_usec     Microseconds on top of that last whole second
     */
    public function getCpuTimeMs(): string
    {
        $endUsage = getrusage();

        if ($this->startUsage === false || $endUsage === false) {
            return '-';
        }

        $cpuTimeSec = $this->rusageToMs($endUsage) - $this->rusageToMs($this->startUsage);

        // Convert from seconds to milliseconds
        return (string) round($cpuTimeSec * 1000);
    }

    public function getHeaderBytes(SymfonyResponse $response): int
    {
        $headerBytes = 0;

        // Non-cookie headers
        foreach ($response->headers->allPreserveCaseWithoutCookies() as $key => $values) {
            foreach ($values as $value) {
                // Format: "Header-Name: value\r\n"
                $headerBytes += strlen($key) + 2 + strlen($value) + 2;
            }
        }
        // Cookies
        foreach ($response->headers->getCookies() as $cookie) {
            $cookieLine = 'Set-Cookie: ' . $cookie->__toString();
            $headerBytes += strlen($cookieLine) + 2;
        }

        return $headerBytes;
    }

    public function getBodyBytes(SymfonyResponse $response): int
    {
        $body = $response->getContent();

        if ($body === false) {
            return 0;
        }

        // Ensure php.ini:mbstring.func_overload=0, otherwise use: mb_strlen($body, '8bit')
        return strlen($body);
    }

    /**
     * @param array<string, int> $rusage
     * @return float
     */
    protected function rusageToMs(array $rusage): float
    {
        return $rusage['ru_utime.tv_sec'] + $rusage['ru_utime.tv_usec'] / 1e6 + $rusage['ru_stime.tv_sec'] + $rusage['ru_stime.tv_usec'] / 1e6;
    }
}
