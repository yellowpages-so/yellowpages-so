<?php

use App\Http\Controllers\Api\AdminCmsContentController;
use App\Http\Controllers\Api\CmsContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/cms')->group(function (): void {
    Route::get('/pages', [CmsContentController::class, 'pages']);
    Route::get('/pages/{slug}', [CmsContentController::class, 'page']);
    Route::get('/posts', [CmsContentController::class, 'posts']);
    Route::get('/posts/{slug}', [CmsContentController::class, 'post']);
    Route::get('/banners', [CmsContentController::class, 'banners']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/admin/pages', [AdminCmsContentController::class, 'createPage']);
        Route::post('/admin/posts', [AdminCmsContentController::class, 'createPost']);
        Route::get('/admin/dashboard', [AdminCmsContentController::class, 'dashboard']);
    });
});
