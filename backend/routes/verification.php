<?php

use App\Http\Controllers\Api\AdminVerificationController;
use App\Http\Controllers\Api\BusinessClaimController;
use App\Http\Controllers\Api\VerificationRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::post(
            '/businesses/{business}/claims',
            [BusinessClaimController::class, 'store']
        );

        Route::get(
            '/verification-requests',
            [VerificationRequestController::class, 'index']
        );

        Route::post(
            '/businesses/{business}/verification-requests',
            [VerificationRequestController::class, 'store']
        );

        Route::post(
            '/verification-requests/{verificationRequest}/documents',
            [VerificationRequestController::class, 'uploadDocument']
        );

        Route::get(
            '/admin/verification-requests',
            [AdminVerificationController::class, 'index']
        );

        Route::post(
            '/admin/verification-requests/{verificationRequest}/decision',
            [AdminVerificationController::class, 'decide']
        );
    });
