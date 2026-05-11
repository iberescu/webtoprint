<?php

use Shop\Http\Controllers\CartController;
use Shop\Http\Controllers\CheckoutController;
use Shop\Http\Controllers\CustomerAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok', 'service' => 'shop']);

Route::prefix('storefront')->group(function () {
    Route::post('/cart', [CartController::class, 'create']);
    Route::get('/cart/{cart}', [CartController::class, 'show'])->whereNumber('cart');
    Route::post('/cart/{cart}/items', [CartController::class, 'addItem'])->whereNumber('cart');
    Route::delete('/cart/{cart}/items/{item}', [CartController::class, 'removeItem'])
        ->whereNumber(['cart', 'item']);

    Route::post('/checkout/{cart}', [CheckoutController::class, 'placeOrder'])->whereNumber('cart');

    Route::post('/auth/register', [CustomerAuthController::class, 'register']);
    Route::post('/auth/login', [CustomerAuthController::class, 'login']);
    Route::middleware('auth:sanctum')->get('/auth/me', [CustomerAuthController::class, 'me']);
});
