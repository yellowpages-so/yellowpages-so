<?php

use App\Http\Controllers\Api\AdminAnalyticsReportingController;
use App\Http\Controllers\Api\AnalyticsReportingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/reporting')->group(function (): void {
    Route::post('/events', [AnalyticsReportingController::class, 'track']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/businesses/{businessId}/dashboard', [AnalyticsReportingController::class, 'dashboard']);
        Route::get('/reports', [AnalyticsReportingController::class, 'reports']);
        Route::post('/reports', [AnalyticsReportingController::class, 'saveReport']);
        Route::get('/admin/dashboard', [AdminAnalyticsReportingController::class, 'dashboard']);
    });
});
