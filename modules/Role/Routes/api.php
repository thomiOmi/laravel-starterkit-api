<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Role\Controllers\RoleController;

Route::prefix('roles')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/', [RoleController::class, 'index'])->middleware('can:role.view')->name('index');
    Route::post('/', [RoleController::class, 'store'])->middleware('can:role.create')->name('store');
    Route::post('/bulk', [RoleController::class, 'bulkAction'])->middleware('can:role.edit')->name('bulk');
    Route::get('/{role}', [RoleController::class, 'show'])->middleware('can:role.view')->name('show');
    Route::put('/{role}', [RoleController::class, 'update'])->middleware('can:role.edit')->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('can:role.delete')->name('destroy');
});
