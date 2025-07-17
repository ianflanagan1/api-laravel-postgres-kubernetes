<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class LogService
{
    /**
     * Logs a detailed exception error.
     *
     * @param Throwable $e The exception object.
     * @param string $message The custom message for the log.
     * @param array<string, mixed> $context Additional context data for the log.
     * @param bool $addTrace Add the stack trace to the log or not.
     * @return void
     */
    public static function exception(
        Throwable $e,
        string $message = 'Exception',
        array $context = [],
        bool $addTrace = false
    ): void {
        if ($addTrace) {
            $context = array_merge($context, [$e->getTraceAsString()]);
        }

        Log::error(
            "{$message}: {$e->getMessage()} {$e->getFile()}({$e->getLine()}) {$e->getCode()} " . get_class($e),
            $context
        );
    }

    /**
     * Logs the message and location of an unexpected error.
     *
     * @param string $message The custom message for the log.
     * @param array<string, mixed> $context Additional context data for the log.
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        $backtrace = debug_backtrace();
        $caller = $backtrace[1] ?? null;

        $callerClass = $caller['class'] ?? 'NA';
        $callerLine = $caller['line'] ?? 'NA';
        $callerFunction = $caller['function'] ?? 'NA';

        Log::error(
            "{$message}: {$callerClass}:{$callerLine} {$callerFunction}()",
            $context
        );
    }
}
