<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\V1\AuthController;

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

    Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::get('devices', [AuthController::class, 'devices'])->name('devices');
        Route::delete('devices/{id}', [AuthController::class, 'logoutDevice'])->name('devices.logout');
        Route::post('devices/logout-others', [AuthController::class, 'logoutOtherDevices'])->name('devices.logout-others');
    });

    Route::post('email/verify/resend', [AuthController::class, 'resendVerificationEmail'])
        ->middleware(['auth:sanctum', 'throttle:6,1'])
        ->name('verification.send');
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed'])
        ->name('verification.verify');
});
