<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Role\Controllers\V1\BulkDeleteRolesController;
use Modules\Role\Controllers\V1\BulkRestoreRolesController;
use Modules\Role\Controllers\V1\DestroyController as RoleDestroyController;
use Modules\Role\Controllers\V1\IndexController as RoleIndexController;
use Modules\Role\Controllers\V1\PermissionDestroyController;
use Modules\Role\Controllers\V1\PermissionIndexController;
use Modules\Role\Controllers\V1\PermissionShowController;
use Modules\Role\Controllers\V1\PermissionStoreController;
use Modules\Role\Controllers\V1\PermissionUpdateController;
use Modules\Role\Controllers\V1\ShowController as RoleShowController;
use Modules\Role\Controllers\V1\StoreController as RoleStoreController;
use Modules\Role\Controllers\V1\UpdateController as RoleUpdateController;

// Role routes
Route::prefix('roles')->middleware(['force.json', 'auth:sanctum', 'throttle:api'])->name('roles.')->group(function () {
    Route::get('/', RoleIndexController::class)->middleware('can:role.view')->name('index');
    Route::post('/', RoleStoreController::class)->middleware('can:role.create')->name('store');

    Route::post('/bulk/delete', BulkDeleteRolesController::class)->name('bulk.delete');
    Route::post('/bulk/restore', BulkRestoreRolesController::class)->name('bulk.restore');

    Route::get('/{role}', RoleShowController::class)->middleware('can:role.view')->name('show');
    Route::put('/{role}', RoleUpdateController::class)->middleware('can:role.edit')->name('update');
    Route::delete('/{role}', RoleDestroyController::class)->middleware('can:role.delete')->name('destroy');
});

// Permission routes
Route::prefix('permissions')->middleware(['force.json', 'auth:sanctum', 'throttle:api'])->name('permissions.')->group(function () {
    Route::get('/', PermissionIndexController::class)->middleware('can:permission.view')->name('index');
    Route::post('/', PermissionStoreController::class)->middleware('can:permission.create')->name('store');
    Route::get('/{permission}', PermissionShowController::class)->middleware('can:permission.view')->name('show');
    Route::put('/{permission}', PermissionUpdateController::class)->middleware('can:permission.edit')->name('update');
    Route::delete('/{permission}', PermissionDestroyController::class)->middleware('can:permission.delete')->name('destroy');
});
