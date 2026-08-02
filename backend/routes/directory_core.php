<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DirectoryServiceController;
use App\Http\Controllers\Api\PublicDirectoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/tree', [CategoryController::class, 'tree']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/services', [DirectoryServiceController::class, 'index']);
    Route::get('/directory/search', [PublicDirectoryController::class, 'search']);
    Route::get('/businesses/{slug}', [PublicDirectoryController::class, 'show']);
});
