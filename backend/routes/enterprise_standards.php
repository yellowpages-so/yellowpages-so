<?php

use App\Http\Controllers\Api\PlatformHealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/platform')->group(function (): void {
    Route::get('/live', [PlatformHealthController::class, 'live']);
    Route::get('/ready', [PlatformHealthController::class, 'ready']);
});
