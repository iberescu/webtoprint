<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\SettingsController;

Route::middleware('auth:sanctum')->prefix('admin/settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::get('/{key}', [SettingsController::class, 'show'])->name('admin.settings.show');
    Route::put('/{key}', [SettingsController::class, 'update'])->name('admin.settings.update');
    Route::delete('/{key}', [SettingsController::class, 'destroy'])->name('admin.settings.destroy');
});
