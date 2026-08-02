<?php

use App\Http\Controllers\Api\AdminCommunicationController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/notifications')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get(
            '/',
            [NotificationController::class, 'index']
        );

        Route::post(
            '/{notificationId}/read',
            [NotificationController::class, 'markRead']
        );

        Route::post(
            '/read-all',
            [NotificationController::class, 'markAllRead']
        );

        Route::get(
            '/preferences',
            [NotificationController::class, 'preferences']
        );

        Route::put(
            '/preferences',
            [NotificationController::class, 'updatePreferences']
        );

        Route::post(
            '/admin/send',
            [AdminCommunicationController::class, 'send']
        );

        Route::get(
            '/admin/messages',
            [AdminCommunicationController::class, 'messages']
        );

        Route::get(
            '/admin/dashboard',
            [AdminCommunicationController::class, 'dashboard']
        );
    });
