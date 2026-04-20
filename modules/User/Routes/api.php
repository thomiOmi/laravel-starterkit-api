<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\UserController;

Route::prefix('users')->group(function () {
    Route::post('/register', [UserController::class, 'register']);
});
