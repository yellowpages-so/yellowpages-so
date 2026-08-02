<?php

use App\Http\Controllers\Api\AdminReviewController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/businesses/{business}/reviews', [ReviewController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/businesses/{business}/reviews', [ReviewController::class, 'store']);
        Route::post('/reviews/{reviewId}/reply', [ReviewController::class, 'reply']);
        Route::post('/reviews/{reviewId}/helpful', [ReviewController::class, 'helpful']);
        Route::post('/reviews/{reviewId}/report', [ReviewController::class, 'report']);
        Route::get('/admin/reviews', [AdminReviewController::class, 'queue']);
        Route::post('/admin/reviews/{reviewId}/moderate', [AdminReviewController::class, 'moderate']);
    });
});
