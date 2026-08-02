<?php

use App\Http\Controllers\Api\DeveloperPlatformController;
use App\Http\Controllers\Api\PublicDeveloperApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/developer')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/clients', [DeveloperPlatformController::class, 'clients']);
        Route::post('/clients', [DeveloperPlatformController::class, 'createClient']);
        Route::post('/clients/{clientId}/rotate-secret', [DeveloperPlatformController::class, 'rotateSecret']);
        Route::post('/webhooks', [DeveloperPlatformController::class, 'createWebhook']);
        Route::get('/clients/{clientId}/usage', [DeveloperPlatformController::class, 'usage']);
    });

Route::prefix('public/v1')->group(function (): void {
    Route::get('/health', [PublicDeveloperApiController::class, 'health']);

    Route::middleware([
        'api.client:businesses:read',
        'api.usage',
    ])->group(function (): void {
        Route::get('/businesses', [PublicDeveloperApiController::class, 'businesses']);
    });
});
