<?php

use App\Http\Controllers\Api\AdminCustomerSupportController;
use App\Http\Controllers\Api\CustomerSupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/support')->group(function (): void {
    Route::post('/tickets', [CustomerSupportController::class, 'createTicket']);
    Route::get('/articles', [CustomerSupportController::class, 'articles']);
    Route::get('/faqs', [CustomerSupportController::class, 'faqs']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/tickets', [CustomerSupportController::class, 'myTickets']);
        Route::get('/tickets/{ticketId}', [CustomerSupportController::class, 'ticket']);

        Route::get('/admin/dashboard', [AdminCustomerSupportController::class, 'dashboard']);
        Route::get('/admin/tickets', [AdminCustomerSupportController::class, 'tickets']);
        Route::post('/admin/tickets/{ticketId}/reply', [AdminCustomerSupportController::class, 'reply']);
        Route::patch('/admin/tickets/{ticketId}', [AdminCustomerSupportController::class, 'update']);
    });
});
