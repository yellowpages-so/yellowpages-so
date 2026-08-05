<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    $database = DB::selectOne(
        'SELECT current_database() AS database,
                current_user AS username,
                PostGIS_Version() AS postgis'
    );

    return response()->json([
        'status' => 'healthy',
        'application' => 'YellowPages.so API',
        'database' => $database->database,
        'database_user' => $database->username,
        'postgis' => $database->postgis,
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('businesses', BusinessController::class);
});
require __DIR__.'/directory_core.php';
require __DIR__.'/verification.php';
require __DIR__.'/admin_portal.php';
require __DIR__.'/business_owner_portal.php';
require __DIR__.'/smart_search.php';
require __DIR__.'/reviews_reputation.php';
require __DIR__.'/leads_marketplace.php';
require __DIR__.'/advertising_monetization.php';
require __DIR__.'/subscription_billing.php';
require __DIR__.'/ai_business_intelligence.php';
require __DIR__.'/notifications_communication.php';
require __DIR__.'/media_management.php';
require __DIR__.'/developer_integration.php';
require __DIR__.'/security_compliance.php';
require __DIR__.'/marketplace_commerce.php';
require __DIR__.'/payments_platform.php';
require __DIR__.'/analytics_reporting.php';
require __DIR__.'/cms_content.php';
require __DIR__.'/customer_support.php';
require __DIR__.'/workflow_automation.php';

require __DIR__.'/enterprise_standards.php';

require __DIR__.'/enterprise_observability.php';
