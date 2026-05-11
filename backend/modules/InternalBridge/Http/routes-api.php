<?php

use Illuminate\Support\Facades\Route;
use Modules\InternalBridge\Http\Controllers\InternalDesignController;
use Modules\InternalBridge\Http\Controllers\InternalProductController;
use Modules\InternalBridge\Http\Controllers\InternalProductionJobController;
use Modules\InternalBridge\Http\Middleware\VerifyInternalToken;

Route::middleware(VerifyInternalToken::class)->prefix('internal')->group(function () {
    Route::get('products/{id}', [InternalProductController::class, 'show'])
        ->where('id', '[0-9a-f-]{36}');

    Route::get('designs/{id}/preflight', [InternalDesignController::class, 'preflight'])
        ->where('id', '[0-9a-f-]{36}');

    Route::post('production-jobs/batch', [InternalProductionJobController::class, 'batch']);
});
