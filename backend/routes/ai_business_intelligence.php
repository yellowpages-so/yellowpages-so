<?php

use App\Http\Controllers\Api\AiBusinessIntelligenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/ai')->group(function (): void {
    Route::get(
        '/businesses/{business}/recommendations',
        [AiBusinessIntelligenceController::class, 'recommendations']
    );

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post(
            '/businesses/{business}/generate-description',
            [AiBusinessIntelligenceController::class, 'businessDescription']
        );

        Route::post(
            '/businesses/{business}/review-summary',
            [AiBusinessIntelligenceController::class, 'reviewSummary']
        );

        Route::post(
            '/quote-requests/{quoteRequestId}/score',
            [AiBusinessIntelligenceController::class, 'scoreLead']
        );

        Route::post(
            '/risk-scan',
            [AiBusinessIntelligenceController::class, 'riskScan']
        );

        Route::get(
            '/admin/dashboard',
            [AiBusinessIntelligenceController::class, 'adminDashboard']
        );
    });
});
