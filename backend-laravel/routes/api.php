<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Middleware\ApiAuthMiddleware;
use Illuminate\Http\Request;

Route::prefix('v1')->group(
    function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware([
            ApiAuthMiddleware::class.':sanctum',    // Creates $request->user()
            'throttle:api',                         // Relies on $request->user()
        ])->group(function () {
            Route::apiResource('items', ItemController::class);
            Route::post('/logout', [AuthController::class, 'logout']);
        });

        Route::post('/csp-report', function (Request $request) {
            /*
            {
                "csp-report": {
                    "document-uri": "https://example.com/page.html",
                    "referrer": "",
                    "violated-directive": "script-src 'self'",
                    "effective-directive": "script-src",
                    "original-policy": "..................full CSP..................",
                    "blocked-uri": "http://evil.com/evil.js",
                    "status-code": 200,
                    "source-file": "https://example.com/page.html",
                    "line-number": 42,
                    "column-number": 21,
                    "script-sample": "alert('XSS')"
                }
            }
            */

            Log::error('CSP Violation:'.$request->getContent());

            return response()->noContent(204);
        });
    }
);
