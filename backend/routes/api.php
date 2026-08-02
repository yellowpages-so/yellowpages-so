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
