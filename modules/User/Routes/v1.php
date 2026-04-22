<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\V1\UserController;

Route::prefix('users')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/', [UserController::class, 'index'])->middleware('can:user.view')->name('index');
    Route::post('/', [UserController::class, 'store'])->middleware('can:user.create')->name('store');
    Route::post('/bulk', [UserController::class, 'bulkAction'])->middleware('can:user.delete')->name('bulk');
    Route::get('/{user}', [UserController::class, 'show'])->middleware('can:user.view')->name('show');
    Route::put('/{user}', [UserController::class, 'update'])->middleware('can:user.edit')->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('can:user.delete')->name('destroy');
});
