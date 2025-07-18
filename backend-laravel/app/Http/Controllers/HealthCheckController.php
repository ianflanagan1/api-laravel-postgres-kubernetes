<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LogService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class HealthCheckController extends Controller
{
    protected const string STARTUP_PROBE_CACHE_KEY_PREFIX = 'startup_probe_';

    public function startup(): Response
    {
        $errors = [];

        // Check database connection
        try {
            // DB::getPdo();
            DB::select('SELECT 1');

        } catch (Exception $e) {
            $errors[] = 'Database connection failed';
            LogService::exception($e, 'Startup Probe: Database connection failed');
        }

        // Check cache connection
        try {
            // Redis::ping();
            $key = self::STARTUP_PROBE_CACHE_KEY_PREFIX.Str::random(10);
            $value = now()->toDateTimeString();

            Cache::put($key, $value, 5);

            if (Cache::get($key) !== $value) {
                $errors[] = 'Cache connection failed';
                Log::error('Startup Probe: Cache read/write mismatch');
            }

        } catch (Exception $e) {
            $errors[] = 'Cache connection failed';
            LogService::exception($e, 'Startup Probe: Cache connection failed');
        }

        if (! empty($errors)) {
            $output = 'down'.PHP_EOL.implode(PHP_EOL, $errors);

            return response($output, SymfonyResponse::HTTP_SERVICE_UNAVAILABLE)->header('Content-Type', 'text/plain');
        }

        $output = 'up';

        return response($output)->header('Content-Type', 'text/plain');
    }

    public function readiness(): Response
    {
        $output = 'up';

        return response($output)->header('Content-Type', 'text/plain');
    }

    public function status(): Response
    {
        $output = '';

        $output .= 'REALPATH CACHE'.PHP_EOL;
        $output .= 'PID: '.getmypid().PHP_EOL;
        $output .= 'realpath_cache_size() mb: '.(realpath_cache_size() / 1024 / 1024).' / '.ini_get('realpath_cache_size').' mb'.PHP_EOL;
        $output .= PHP_EOL;

        // todo: analyse "hot" classes in production
        $output .= 'OPCACHE'.PHP_EOL;

        $status = opcache_get_status(true);

        if ($status) {
            $output .= $status['cache_full'] ? 'CACHE FULL'.PHP_EOL : '';
            $output .= '[memory_usage][used_memory]:                 '.($status['memory_usage']['used_memory'] / 1024 / 1024).' / '.ini_get('opcache.memory_consumption').' mb'.PHP_EOL;
            $output .= '[opcache_statistics][max_cached_keys]:       '.$status['opcache_statistics']['num_cached_keys'].' / '.ini_get('opcache.max_accelerated_files').PHP_EOL;
            $output .= '[interned_strings_usage][used_memory]:       '.($status['interned_strings_usage']['used_memory'] / 1024 / 1024).' / '.ini_get('opcache.interned_strings_buffer').' mb'.PHP_EOL;
            $output .= '[interned_strings_usage][number_of_strings]: '.$status['interned_strings_usage']['number_of_strings'].PHP_EOL;
            $output .= PHP_EOL;

            $scripts = $status['scripts'];

            // Sort scripts by hits
            usort($scripts, function ($a, $b) {
                return $b['hits'] <=> $a['hits'];
            });

            // Print scripts
            foreach ($scripts as $script) {
                $output .= $script['full_path'].': '.$script['hits'].' hits'.PHP_EOL;
            }
        }

        $output .= PHP_EOL;

        ob_start();
        var_dump(opcache_get_status(true));
        $output .= ob_get_clean();

        return response($output)->header('Content-Type', 'text/plain');
    }
}
