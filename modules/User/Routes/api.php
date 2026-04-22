<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\UserController;

Route::prefix('users')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/', [UserController::class, 'index'])->middleware('can:user.view');
    Route::post('/', [UserController::class, 'store'])->middleware('can:user.create');
    Route::post('/bulk', [UserController::class, 'bulkAction'])->middleware('can:user.delete');
    Route::get('/{user}', [UserController::class, 'show'])->middleware('can:user.view');
    Route::put('/{user}', [UserController::class, 'update'])->middleware('can:user.edit');
    Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('can:user.delete');
});
