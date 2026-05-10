<?php

use Illuminate\Support\Facades\Route;
use Modules\Designer\Http\Controllers\DesignerTemplateController;
use Modules\Designer\Http\Controllers\DesignerDesignController;
use Modules\Designer\Http\Controllers\DesignerPdfUploadController;

Route::prefix('designer')->group(function () {
    // Template browsing (public for end-user template picker)
    Route::get('/templates', [DesignerTemplateController::class, 'index']);
    Route::get('/templates/{template}', [DesignerTemplateController::class, 'show']);

    // Design lifecycle (auth optional — guest carts can hold designs by id)
    Route::post('/designs', [DesignerDesignController::class, 'store']);
    Route::get('/designs/{design}', [DesignerDesignController::class, 'show']);
    Route::put('/designs/{design}', [DesignerDesignController::class, 'update']);
    Route::post('/designs/{design}/preview', [DesignerDesignController::class, 'preview']);
    Route::post('/designs/{design}/generate-print-pdf', [DesignerDesignController::class, 'generatePrintPdf']);
    Route::post('/designs/{design}/approve', [DesignerDesignController::class, 'approve']);

    // PDF upload alternative
    Route::post('/pdf-upload', [DesignerPdfUploadController::class, 'create']);
    Route::post('/pdf-upload/{design}/validate', [DesignerPdfUploadController::class, 'validate']);
    Route::post('/pdf-upload/{design}/approve', [DesignerPdfUploadController::class, 'approve']);

    // Admin template CRUD
    Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
        Route::post('/templates', [DesignerTemplateController::class, 'store']);
        Route::put('/templates/{template}', [DesignerTemplateController::class, 'update']);
        Route::delete('/templates/{template}', [DesignerTemplateController::class, 'destroy']);
    });
});
