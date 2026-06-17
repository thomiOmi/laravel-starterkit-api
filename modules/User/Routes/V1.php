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
    Route::get('/', IndexController::class)->middleware('can:user.view')->name('index');
    Route::post('/', CreateController::class)->middleware('can:user.create')->name('create');

    // Split Bulk Actions
    Route::post('/bulk/delete', BulkDeleteController::class)->name('bulk.delete');
    Route::post('/bulk/restore', BulkRestoreController::class)->name('bulk.restore');

    Route::get('/{user}', ShowController::class)->middleware('can:user.view')->name('show');
    Route::put('/{user}', UpdateController::class)->middleware('can:user.edit')->name('update');
    Route::delete('/{user}', DeleteController::class)->middleware('can:user.delete')->name('delete');
});
