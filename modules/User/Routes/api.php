<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\UserController;

Route::prefix('users')->group(function () {
    Route::post('/register', [UserController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [UserController::class, 'index']);
    });
});
