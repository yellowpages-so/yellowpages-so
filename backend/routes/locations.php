<?php

use App\Http\Controllers\Api\LocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/locations')->group(function (): void {
    Route::get('/regions', [LocationController::class, 'regions']);
    Route::get('/cities', [LocationController::class, 'cities']);
    Route::get('/districts', [LocationController::class, 'districts']);
});
