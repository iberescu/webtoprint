<?php

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Http\Controllers\PriceController;

Route::post('/products/{slug}/price', [PriceController::class, 'calculate'])->name('products.price');
