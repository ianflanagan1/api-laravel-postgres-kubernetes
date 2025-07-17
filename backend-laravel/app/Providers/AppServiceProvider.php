<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\RequestContextService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind as a request-scoped singleton
        $this->app->singleton(RequestContextService::class, function ($app) {
            return new RequestContextService($app->make(Request::class));
        });

        // Dev only
        if ($this->app->environment('local')) {

            // Telescope
            if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
                $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
                $this->app->register(TelescopeServiceProvider::class);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Initiate RequestContextService singleton as early as possible to set request start time
        $requestContextService = $this->app->make(RequestContextService::class);

        // Log DB query durations
        DB::listen(function ($query) use ($requestContextService) {
            $requestContextService->addDbDuration($query->time);
        });

        // Configure API rate limit
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id);
        });

        // Dev only
        if ($this->app->environment('local')) {

            // Disable garbage collection for profiling, according to XDEBUG_DISABLE_GC in .env
            if (config('xdebug.disable_gc')) {
                gc_disable();
                Log::info('PHP Garbage Collector disabled for profiling.');
            }
        }
    }
}
