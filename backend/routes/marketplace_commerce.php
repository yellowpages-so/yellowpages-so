<?php

use App\Http\Controllers\Api\AdminCommerceController;
use App\Http\Controllers\Api\CommerceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/commerce')->group(function (): void {
    Route::get('/products', [CommerceController::class, 'products']);
    Route::post('/cart/items', [CommerceController::class, 'addToCart']);
    Route::get('/carts/{cartId}', [CommerceController::class, 'cart']);
    Route::post('/checkout', [CommerceController::class, 'checkout']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/products', [CommerceController::class, 'storeProduct']);
        Route::get('/owner/orders', [CommerceController::class, 'ownerOrders']);

        Route::get('/admin/dashboard', [AdminCommerceController::class, 'dashboard']);
        Route::get('/admin/orders', [AdminCommerceController::class, 'orders']);
        Route::patch('/admin/orders/{orderId}', [AdminCommerceController::class, 'updateOrder']);
    });
});
