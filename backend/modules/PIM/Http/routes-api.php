<?php

use Illuminate\Support\Facades\Route;
use Modules\PIM\Http\Controllers\ProductCategoryController;
use Modules\PIM\Http\Controllers\ProductController;
use Modules\PIM\Http\Controllers\ConfiguratorController;
use Modules\PIM\Http\Controllers\ProductOptionController;
use Modules\PIM\Http\Controllers\ProductOptionValueController;
use Modules\PIM\Http\Controllers\ProductRuleController;

// --- Public catalogue + configurator (no auth) ---
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/products/{slug}/configurator', [ConfiguratorController::class, 'show'])->name('products.configurator');
Route::post('/products/{slug}/validate', [ConfiguratorController::class, 'validate'])->name('products.validate');

// --- Admin CRUD (auth required) ---
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('products', ProductController::class)->except(['show', 'index']);
    Route::get('products', [ProductController::class, 'adminIndex']);
    Route::get('products/{product}/admin', [ProductController::class, 'adminShow']);

    Route::apiResource('products.options', ProductOptionController::class)->shallow();
    Route::apiResource('options.values', ProductOptionValueController::class)->shallow()->parameters([
        'options' => 'option', 'values' => 'value',
    ]);
    Route::apiResource('products.rules', ProductRuleController::class)->shallow();
});
