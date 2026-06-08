<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\V1\LoginController;
use Modules\Auth\Controllers\V1\LogoutController;
use Modules\Auth\Controllers\V1\MeController;
use Modules\Auth\Controllers\V1\RegisterController;
use Modules\Auth\Controllers\V1\VerifyEmailController;

Route::prefix('auth')->middleware(['force.json'])->group(function () {
    Route::post('register', RegisterController::class)->middleware('throttle:auth')->name('register');
    Route::post('login', LoginController::class)->middleware('throttle:auth')->name('login');

    Route::prefix('email')->name('verification.')->group(function () {
        Route::get('verify/{id}/{hash}', VerifyEmailController::class)
            ->middleware('signed')
            ->name('verify');
    });

    Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function () {
        Route::post('logout', LogoutController::class)->name('logout');
        Route::get('me', MeController::class)->name('me');
    });
});
