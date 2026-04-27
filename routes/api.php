<?php

use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileDocumentController;
use App\Http\Controllers\Api\MobilePosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and are assigned
| the "api" middleware group.
|
*/

// Payment Gateway Webhooks (no auth required)
Route::prefix('webhooks')->group(function () {
    Route::post('/midtrans', [PaymentWebhookController::class, 'midtrans'])->name('webhooks.midtrans');
    Route::post('/xendit', [PaymentWebhookController::class, 'xendit'])->name('webhooks.xendit');
});

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/logout', [MobileAuthController::class, 'logout']);

        Route::get('/bootstrap', [MobilePosController::class, 'bootstrap']);
        Route::get('/products', [MobilePosController::class, 'products']);
        Route::get('/customers', [MobilePosController::class, 'customers']);
        Route::post('/customers', [MobilePosController::class, 'storeCustomer']);
        Route::get('/customers/{customer}/history', [MobilePosController::class, 'customerHistory']);

        Route::get('/cart', [MobilePosController::class, 'cart']);
        Route::post('/cart/items', [MobilePosController::class, 'addCartItem']);
        Route::patch('/cart/items/{cart}', [MobilePosController::class, 'updateCartItem']);
        Route::delete('/cart/items/{cart}', [MobilePosController::class, 'removeCartItem']);
        Route::get('/cart/held', [MobilePosController::class, 'heldCarts']);
        Route::post('/cart/hold', [MobilePosController::class, 'holdCart']);
        Route::post('/cart/held/{holdId}/resume', [MobilePosController::class, 'resumeHeldCart']);
        Route::delete('/cart/held/{holdId}', [MobilePosController::class, 'clearHeldCart']);

        Route::post('/checkout', [MobilePosController::class, 'checkout']);
        Route::get('/transactions', [MobilePosController::class, 'transactions']);
        Route::get('/transactions/{invoice}/documents/{variant}', [MobileDocumentController::class, 'show']);
        Route::get('/transactions/{invoice}', [MobilePosController::class, 'showTransaction']);
    });
});
