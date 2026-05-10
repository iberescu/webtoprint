<?php

use Illuminate\Support\Facades\Route;
use Modules\Templates\Http\Controllers\ContentTemplateController;

Route::middleware('auth:sanctum')->prefix('admin/templates')->group(function () {
    Route::get('/', [ContentTemplateController::class, 'index']);
    Route::post('/', [ContentTemplateController::class, 'store']);
    Route::get('/{template}', [ContentTemplateController::class, 'show']);
    Route::put('/{template}', [ContentTemplateController::class, 'update']);
    Route::delete('/{template}', [ContentTemplateController::class, 'destroy']);
    Route::get('/{template}/versions', [ContentTemplateController::class, 'versions']);
    Route::post('/{template}/restore/{version}', [ContentTemplateController::class, 'restore']);
});
