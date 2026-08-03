<?php

use App\Http\Controllers\Api\AdminPaymentController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/payments')->group(function (): void {
    Route::get('/providers', [PaymentController::class, 'providers']);
    Route::post('/intents', [PaymentController::class, 'createIntent']);
    Route::post('/intents/{intentId}/capture', [PaymentController::class, 'capture']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/intents/{intentId}/refund', [PaymentController::class, 'refund']);
        Route::get('/admin/dashboard', [AdminPaymentController::class, 'dashboard']);
        Route::get('/admin/intents', [AdminPaymentController::class, 'intents']);
        Route::post('/admin/escrows/{escrowId}/release', [AdminPaymentController::class, 'releaseEscrow']);
    });
});
