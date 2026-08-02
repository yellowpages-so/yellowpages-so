<?php

use App\Http\Controllers\Api\AdminAdvertisingController;
use App\Http\Controllers\Api\AdvertisingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/advertising')->group(function (): void {
    Route::get('/placements', [AdvertisingController::class, 'placements']);
    Route::get('/slots/{placementCode}', [AdvertisingController::class, 'slot']);
    Route::get('/click/{creativeId}', [AdvertisingController::class, 'click']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/campaigns', [AdvertisingController::class, 'storeCampaign']);
        Route::post('/campaigns/{campaignId}/creatives', [AdvertisingController::class, 'storeCreative']);
        Route::get('/analytics', [AdvertisingController::class, 'analytics']);

        Route::get('/admin/campaigns', [AdminAdvertisingController::class, 'queue']);
        Route::post('/admin/campaigns/{campaignId}/decision', [AdminAdvertisingController::class, 'decide']);
    });
});
