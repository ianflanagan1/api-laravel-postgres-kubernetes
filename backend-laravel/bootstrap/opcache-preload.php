<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use SplFileInfo;

// This script will be run once by the PHP-FPM master process on startup.
// Configured at php.ini `opcache.preload`

set_time_limit(0);
ini_set('memory_limit', '-1');

echo 'Preloading started' . PHP_EOL;

$appRoot = dirname(__DIR__);

// Use Composer's autoloader to cache dependency classes used by the application
// More memory-efficient than running: `preload_files_from_dir($appRoot . '/vendor');`
try {
    require_once $appRoot . '/vendor/autoload.php';

    foreach (ClassLoader::getRegisteredLoaders() as $loader) {
        foreach ($loader->getClassMap() as $class => $file) {
            if (file_exists($file) && is_file($file)) {
                cache_file($file);
            }
        }
    }
} catch (Throwable $e) {
    error_log("Failed to preload Composer autoloader or class map: " . $e->getMessage());
}

opcache_compile_file($appRoot . '/public/index.php');
preload_files_from_dir($appRoot . '/bootstrap');
preload_files_from_dir($appRoot . '/storage/framework/views');

// app                Included in autoload.php
// config             Compiled by `php artisan config:cache` into bootstrap/cache/config.php
// database           Migrations and seeders only run at startup
// resources/views    Deleted in production. Compiled by `php artisan view:cache` into storage/framework/views
// routes             Deleted in production. Compiled by `php artisan route:cache` into bootstrap/cache/routes-v7.php
// vendor             Included in autoload.php

echo 'Preloading finished' . PHP_EOL;

// Function to recursively compile PHP files
function preload_files_from_dir(string $directory): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        if ($file->isFile() && $file->getExtension() === 'php' && $file->getPathname() != '/app/bootstrap/opcache-preload.php') {
            try {
                cache_file($file->getPathname());
            } catch (Throwable $e) {
                error_log("Failed to preload file: " . $file->getPathname() . " - " . $e->getMessage());
            }
        }
    }
}

function cache_file(string $file): void
{
    opcache_compile_file($file);
    // echo "Preloaded: " . $file . PHP_EOL;
}
