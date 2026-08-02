<?php

use App\Http\Controllers\Api\SmartSearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/search')->group(function (): void {
    Route::get('/', [SmartSearchController::class, 'search']);
    Route::get('/suggestions', [SmartSearchController::class, 'suggestions']);
    Route::get('/popular', [SmartSearchController::class, 'popular']);
});
