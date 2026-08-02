<?php

use App\Http\Controllers\Api\AdminMediaController;
use App\Http\Controllers\Api\MediaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/media')->group(function (): void {
    Route::get('/collection', [MediaController::class, 'collection']);
    Route::get('/{assetId}', [MediaController::class, 'show']);
    Route::get('/{assetId}/download', [MediaController::class, 'download'])
        ->name('media.download');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/', [MediaController::class, 'upload']);
        Route::patch('/{assetId}', [MediaController::class, 'update']);
        Route::delete('/{assetId}', [MediaController::class, 'destroy']);

        Route::get('/admin/queue', [AdminMediaController::class, 'queue']);
        Route::post('/admin/{assetId}/moderate', [AdminMediaController::class, 'moderate']);
        Route::get('/admin/dashboard', [AdminMediaController::class, 'dashboard']);
    });
});
