<?php

use App\Http\Controllers\Api\AdminLeadMarketplaceController;
use App\Http\Controllers\Api\LeadMarketplaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post(
        '/quote-requests',
        [LeadMarketplaceController::class, 'store']
    );

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get(
            '/owner/leads',
            [LeadMarketplaceController::class, 'inbox']
        );

        Route::get(
            '/owner/leads/analytics',
            [LeadMarketplaceController::class, 'analytics']
        );

        Route::post(
            '/quote-requests/{quoteRequestId}/businesses/{businessId}/responses',
            [LeadMarketplaceController::class, 'respond']
        );

        Route::patch(
            '/owner/lead-assignments/{assignmentId}',
            [LeadMarketplaceController::class, 'updateStatus']
        );

        Route::get(
            '/admin/quote-requests',
            [AdminLeadMarketplaceController::class, 'index']
        );
    });
});
