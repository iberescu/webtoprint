<?php

use Illuminate\Support\Facades\Route;
use Modules\FileStorage\Http\Controllers\FilesController;

Route::middleware('auth:sanctum')->prefix('files')->group(function () {
    Route::post('/presign-upload', [FilesController::class, 'presign'])->name('files.presign');
    Route::post('/', [FilesController::class, 'register'])->name('files.register');
    Route::get('/{file}', [FilesController::class, 'show'])->name('files.show');
    Route::get('/{file}/download', [FilesController::class, 'download'])->name('files.download');
    Route::delete('/{file}', [FilesController::class, 'destroy'])->name('files.destroy');
});
