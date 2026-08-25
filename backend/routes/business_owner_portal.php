<?php

use App\Http\Controllers\Api\BusinessOwnerPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/owner')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/dashboard', [BusinessOwnerPortalController::class, 'dashboard']);

        Route::post('/businesses/{business}/addresses', [BusinessOwnerPortalController::class, 'storeAddress']);

        Route::get('/businesses/{business}/progress', [BusinessOwnerPortalController::class, 'progress']);

        Route::get('/businesses/{business}/categories', [BusinessOwnerPortalController::class, 'categories']);
        Route::put('/businesses/{business}/categories', [BusinessOwnerPortalController::class, 'updateCategories']);
        Route::get('/businesses/{business}/verification-status', [BusinessOwnerPortalController::class, 'verificationStatus']);

        Route::get('/businesses/{business}/branches', [BusinessOwnerPortalController::class, 'branches']);
        Route::post('/businesses/{business}/branches', [BusinessOwnerPortalController::class, 'storeBranch']);

        Route::get('/businesses/{business}/contacts', [BusinessOwnerPortalController::class, 'contacts']);
        Route::post('/businesses/{business}/contacts', [BusinessOwnerPortalController::class, 'storeContact']);

        Route::get('/businesses/{business}/social-links', [BusinessOwnerPortalController::class, 'socialLinks']);
        Route::post('/businesses/{business}/social-links', [BusinessOwnerPortalController::class, 'storeSocialLink']);

        Route::put('/businesses/{business}/opening-hours', [BusinessOwnerPortalController::class, 'storeHours']);

        Route::get('/businesses/{business}/services', [BusinessOwnerPortalController::class, 'services']);
        Route::post('/businesses/{business}/services', [BusinessOwnerPortalController::class, 'storeService']);

        Route::get('/businesses/{business}/team', [BusinessOwnerPortalController::class, 'team']);
        Route::post('/businesses/{business}/team', [BusinessOwnerPortalController::class, 'storeTeamMember']);

        Route::get('/businesses/{business}/analytics', [BusinessOwnerPortalController::class, 'analytics']);
    });
