<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ApiKey\Controllers\V1\ApiKeyController;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('api-keys', [ApiKeyController::class, 'index'])->name('index');
    Route::post('api-keys', [ApiKeyController::class, 'store'])->name('store');
    Route::delete('api-keys/{id}', [ApiKeyController::class, 'destroy'])->name('destroy');
});
