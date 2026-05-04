<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Media\Controllers\V1\MediaController;

Route::prefix('media')->middleware(['auth:sanctum,tenancy.request', 'throttle:authenticated'])->group(function () {
    Route::get('/', [MediaController::class, 'index'])->name('index');
    Route::post('/', [MediaController::class, 'store'])->name('store');
    Route::delete('{id}', [MediaController::class, 'destroy'])->name('destroy');
});
