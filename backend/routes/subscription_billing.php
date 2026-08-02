<?php

use App\Http\Controllers\Api\AdminSubscriptionBillingController;
use App\Http\Controllers\Api\SubscriptionBillingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/billing')->group(function (): void {
    Route::get('/plans', [SubscriptionBillingController::class, 'plans']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/subscriptions', [SubscriptionBillingController::class, 'start']);
        Route::get('/businesses/{businessId}/subscription', [SubscriptionBillingController::class, 'current']);
        Route::post('/subscriptions/{subscriptionId}/change-plan', [SubscriptionBillingController::class, 'changePlan']);
        Route::post('/subscriptions/{subscriptionId}/cancel', [SubscriptionBillingController::class, 'cancel']);
        Route::get('/businesses/{businessId}/invoices', [SubscriptionBillingController::class, 'invoices']);

        Route::get('/admin/plans', [AdminSubscriptionBillingController::class, 'plans']);
        Route::post('/admin/plans', [AdminSubscriptionBillingController::class, 'storePlan']);
        Route::get('/admin/subscriptions', [AdminSubscriptionBillingController::class, 'subscriptions']);
        Route::get('/admin/revenue', [AdminSubscriptionBillingController::class, 'revenue']);
    });
});
