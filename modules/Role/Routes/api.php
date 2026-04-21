<?php

use Illuminate\Support\Facades\Route;
use Modules\Role\Controllers\RoleController;

Route::prefix('v1/roles')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/', [RoleController::class, 'index'])->middleware('can:user.view');
    Route::post('/', [RoleController::class, 'store'])->middleware('can:user.create');
    Route::get('/{role}', [RoleController::class, 'show'])->middleware('can:user.view');
    Route::put('/{role}', [RoleController::class, 'update'])->middleware('can:user.edit');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('can:user.delete');
});
