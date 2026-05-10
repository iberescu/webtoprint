<?php

use Illuminate\Support\Facades\Route;
use Modules\Distribution\Http\Controllers\ProductionJobController;

Route::middleware('auth:sanctum')->prefix('production')->group(function () {
    Route::get('jobs', [ProductionJobController::class, 'index']);
    Route::post('jobs', [ProductionJobController::class, 'store']);
    Route::get('jobs/{job}', [ProductionJobController::class, 'show']);
    Route::post('jobs/{job}/generate-package', [ProductionJobController::class, 'generatePackage']);
    Route::post('jobs/{job}/regenerate-jobsheet', [ProductionJobController::class, 'regenerate']);
    Route::post('jobs/{job}/regenerate-jdf', [ProductionJobController::class, 'regenerate']);
    Route::post('jobs/{job}/regenerate-mxml', [ProductionJobController::class, 'regenerate']);
    Route::get('jobs/{job}/download', [ProductionJobController::class, 'download']);
});
