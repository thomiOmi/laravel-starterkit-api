<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\UserController;

Route::prefix('users')->middleware('throttle:api')->group(function () {
    Route::post('/register', [UserController::class, 'register'])->middleware('throttle:auth');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [UserController::class, 'index']);
    });
});
