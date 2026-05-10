<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AdminAuthController;

Route::prefix('admin/auth')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.auth.logout');
        Route::get('/me', [AdminAuthController::class, 'me'])->name('admin.auth.me');
    });
});
