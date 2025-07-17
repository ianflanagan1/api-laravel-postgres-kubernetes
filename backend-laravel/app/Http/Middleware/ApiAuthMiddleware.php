<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Log;

class ApiAuthMiddleware extends Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // Call the parent's handle method to preserve authentication logic
        $response = parent::handle($request, $next, ...$guards);

        if ($request->user()) {
            Log::withContext(['user_id' => $request->user()->id]);
        }

        return $response;
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  array<string> $guards
     * @return never
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards): never
    {
        // Don't allow Sanctum to redirect to /login for requests without Accept: application/json header

        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            // $request->expectsJson() ? null : $this->redirectTo($request),
        );
    }
}
