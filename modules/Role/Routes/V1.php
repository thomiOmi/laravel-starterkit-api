<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Role\Controllers\V1\BulkDeleteRolesController;
use Modules\Role\Controllers\V1\BulkRestoreRolesController;
use Modules\Role\Controllers\V1\CreateController as RoleCreateController;
use Modules\Role\Controllers\V1\DeleteController as RoleDeleteController;
use Modules\Role\Controllers\V1\IndexController as RoleIndexController;
use Modules\Role\Controllers\V1\PermissionCreateController;
use Modules\Role\Controllers\V1\PermissionDeleteController;
use Modules\Role\Controllers\V1\PermissionIndexController;
use Modules\Role\Controllers\V1\PermissionShowController;
use Modules\Role\Controllers\V1\PermissionUpdateController;
use Modules\Role\Controllers\V1\ShowController as RoleShowController;
use Modules\Role\Controllers\V1\UpdateController as RoleUpdateController;

// Role routes
Route::prefix('roles')->middleware(['auth:sanctum', 'verified', 'throttle:api'])->group(function () {
    Route::get('/', RoleIndexController::class)->name('index');
    Route::post('/', RoleCreateController::class)->name('create');

    Route::post('/bulk/delete', BulkDeleteRolesController::class)->name('bulk.delete');
    Route::post('/bulk/restore', BulkRestoreRolesController::class)->name('bulk.restore');

    Route::get('/{role}', RoleShowController::class)->name('show');
    Route::put('/{role}', RoleUpdateController::class)->name('update');
    Route::delete('/{role}', RoleDeleteController::class)->name('delete');
});

// Permission routes
Route::prefix('permissions')->middleware(['auth:sanctum', 'verified', 'throttle:api'])->name('permissions.')->group(function () {
    Route::get('/', PermissionIndexController::class)->name('index');
    Route::post('/', PermissionCreateController::class)->name('create');
    Route::get('/{permission}', PermissionShowController::class)->name('show');
    Route::put('/{permission}', PermissionUpdateController::class)->name('update');
    Route::delete('/{permission}', PermissionDeleteController::class)->name('delete');
});
