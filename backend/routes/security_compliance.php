<?php

use App\Http\Controllers\Api\AdminSecurityComplianceController;
use App\Http\Controllers\Api\SecurityComplianceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/security')->middleware('auth:sanctum')->group(function (): void {
    Route::post('/mfa/enable', [SecurityComplianceController::class, 'enableMfa']);
    Route::post('/mfa/confirm', [SecurityComplianceController::class, 'confirmMfa']);
    Route::post('/mfa/disable', [SecurityComplianceController::class, 'disableMfa']);
    Route::get('/sessions', [SecurityComplianceController::class, 'sessions']);
    Route::post('/sessions', [SecurityComplianceController::class, 'createSession']);
    Route::post('/sessions/revoke-all', [SecurityComplianceController::class, 'revokeAllSessions']);
    Route::post('/sessions/{sessionId}/revoke', [SecurityComplianceController::class, 'revokeSession']);
    Route::get('/admin/dashboard', [AdminSecurityComplianceController::class, 'dashboard']);
    Route::get('/admin/alerts', [AdminSecurityComplianceController::class, 'alerts']);
    Route::get('/admin/audit-logs', [AdminSecurityComplianceController::class, 'auditLogs']);
});

Route::post('/v1/compliance/privacy-requests', [SecurityComplianceController::class, 'privacyRequest']);
