<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\V1\BulkActionController;
use Modules\User\Controllers\V1\DestroyController;
use Modules\User\Controllers\V1\IndexController;
use Modules\User\Controllers\V1\ShowController;
use Modules\User\Controllers\V1\StoreController;
use Modules\User\Controllers\V1\UpdateController;

Route::prefix('users')->middleware(['force.json', 'auth:sanctum', 'throttle:api'])->name('users.')->group(function () {
    Route::get('/', IndexController::class)->middleware('can:user.view')->name('index');
    Route::post('/', StoreController::class)->middleware('can:user.create')->name('store');
    Route::post('/bulk', BulkActionController::class)->name('bulk');
    Route::get('/{user}', ShowController::class)->middleware('can:user.view')->name('show');
    Route::put('/{user}', UpdateController::class)->middleware('can:user.edit')->name('update');
    Route::delete('/{user}', DestroyController::class)->middleware('can:user.delete')->name('destroy');
});
