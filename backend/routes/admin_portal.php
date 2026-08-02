<?php

use App\Http\Controllers\Api\AdminPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/dashboard', [AdminPortalController::class, 'dashboard']);
        Route::get('/users', [AdminPortalController::class, 'users']);
        Route::patch('/users/{userId}/status', [AdminPortalController::class, 'updateUserStatus']);
        Route::get('/businesses', [AdminPortalController::class, 'businesses']);
        Route::patch('/businesses/{businessId}/status', [AdminPortalController::class, 'updateBusinessStatus']);
        Route::get('/verification-queue', [AdminPortalController::class, 'verificationQueue']);
        Route::get('/audit-log', [AdminPortalController::class, 'auditLog']);
        Route::post('/notes/{entityType}/{entityId}', [AdminPortalController::class, 'addNote']);
    });
