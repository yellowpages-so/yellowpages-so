<?php

use App\Http\Controllers\Api\AuthController;
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

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

