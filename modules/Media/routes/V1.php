<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\MediaController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('media', MediaController::class);
});