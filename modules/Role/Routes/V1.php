<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Role\Controllers\V1\BulkDeleteRolesController;
use Modules\Role\Controllers\V1\BulkRestoreRolesController;
use Modules\Role\Controllers\V1\DestroyController;
use Modules\Role\Controllers\V1\IndexController;
use Modules\Role\Controllers\V1\ShowController;
use Modules\Role\Controllers\V1\StoreController;
use Modules\Role\Controllers\V1\UpdateController;

Route::prefix('roles')->middleware(['force.json', 'auth:sanctum', 'throttle:api'])->name('roles.')->group(function () {
    Route::get('/', IndexController::class)->middleware('can:role.view')->name('index');
    Route::post('/', StoreController::class)->middleware('can:role.create')->name('store');

    // Split Bulk Actions
    Route::post('/bulk/delete', BulkDeleteRolesController::class)->name('bulk.delete');
    Route::post('/bulk/restore', BulkRestoreRolesController::class)->name('bulk.restore');

    Route::get('/{role}', ShowController::class)->middleware('can:role.view')->name('show');
    Route::put('/{role}', UpdateController::class)->middleware('can:role.edit')->name('update');
    Route::delete('/{role}', DestroyController::class)->middleware('can:role.delete')->name('destroy');
});
