<?php

declare(strict_types=1);

use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/laravel-startup', [HealthCheckController::class, 'startup']);
Route::get('/laravel-readiness', [HealthCheckController::class, 'readiness']);
Route::get('/laravel-status', [HealthCheckController::class, 'status']);
