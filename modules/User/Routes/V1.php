<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\V1\BulkDeleteController;
use Modules\User\Controllers\V1\BulkRestoreController;
use Modules\User\Controllers\V1\CreateController;
use Modules\User\Controllers\V1\DeleteController;
use Modules\User\Controllers\V1\IndexController;
use Modules\User\Controllers\V1\ShowController;
use Modules\User\Controllers\V1\UpdateController;

Route::prefix('users')->middleware(['force.json', 'auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/', IndexController::class)->name('index');
    Route::post('/', CreateController::class)->name('create');

    // Split Bulk Actions
    Route::post('/bulk/delete', BulkDeleteController::class)->name('bulk.delete');
    Route::post('/bulk/restore', BulkRestoreController::class)->name('bulk.restore');

    Route::get('/{user}', ShowController::class)->name('show');
    Route::put('/{user}', UpdateController::class)->name('update');
    Route::delete('/{user}', DeleteController::class)->name('delete');
});
