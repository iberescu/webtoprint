<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\CartController;
use Modules\Ecommerce\Http\Controllers\CheckoutController;
use Modules\Ecommerce\Http\Controllers\CustomerAuthController;
use Modules\Ecommerce\Http\Controllers\OrderController;

Route::prefix('storefront')->group(function () {
    // Carts — identified by Vanilo's integer id
    Route::post('/cart', [CartController::class, 'create']);
    Route::get('/cart/{cart}', [CartController::class, 'show'])->whereNumber('cart');
    Route::post('/cart/{cart}/items', [CartController::class, 'addItem'])->whereNumber('cart');
    Route::delete('/cart/{cart}/items/{item}', [CartController::class, 'removeItem'])
        ->whereNumber(['cart', 'item']);

    // Checkout
    Route::post('/checkout/{cart}', [CheckoutController::class, 'placeOrder'])->whereNumber('cart');

    // Customer auth (storefront-side)
    Route::post('/auth/register', [CustomerAuthController::class, 'register']);
    Route::post('/auth/login', [CustomerAuthController::class, 'login']);
    Route::middleware('auth:sanctum')->get('/auth/me', [CustomerAuthController::class, 'me']);
    Route::middleware('auth:sanctum')->get('/orders', [OrderController::class, 'mine']);
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
});
