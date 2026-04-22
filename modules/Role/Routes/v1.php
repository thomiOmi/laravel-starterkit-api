<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Role\Controllers\V1\RoleController;

Route::prefix('roles')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/', [RoleController::class, 'index'])->middleware('can:user.view')->name('index');
    Route::post('/', [RoleController::class, 'store'])->middleware('can:user.create')->name('store');
    Route::post('/bulk', [RoleController::class, 'bulkAction'])->middleware('can:user.edit')->name('bulk');
    Route::get('/{role}', [RoleController::class, 'show'])->middleware('can:user.view')->name('show');
    Route::put('/{role}', [RoleController::class, 'update'])->middleware('can:user.edit')->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('can:user.delete')->name('destroy');
});
