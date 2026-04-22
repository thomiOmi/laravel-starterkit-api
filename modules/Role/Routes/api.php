<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Role\Controllers\RoleController;

Route::prefix('roles')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/', [RoleController::class, 'index'])->middleware('can:user.view');
    Route::post('/', [RoleController::class, 'store'])->middleware('can:user.create');
    Route::post('/bulk', [RoleController::class, 'bulkAction'])->middleware('can:user.edit');
    Route::get('/{role}', [RoleController::class, 'show'])->middleware('can:user.view');
    Route::put('/{role}', [RoleController::class, 'update'])->middleware('can:user.edit');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('can:user.delete');
});
