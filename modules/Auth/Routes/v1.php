<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\V1\AuthController;
use Modules\Auth\Controllers\V1\EmailVerificationController;
use Modules\Auth\Controllers\V1\ForgotPasswordController;
use Modules\Auth\Controllers\V1\LoginController;
use Modules\Auth\Controllers\V1\ProfileController;
use Modules\Auth\Controllers\V1\RegisterController;
use Modules\Auth\Controllers\V1\ResetPasswordController;
use Modules\Auth\Controllers\V1\SocialAuthController;

Route::prefix('auth')->group(function () {
    Route::post('register', [RegisterController::class, 'register'])->name('register');
    Route::post('login', [LoginController::class, 'login'])->name('login');
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum')
        ->name('logout');

    Route::post('forgot-password', [ForgotPasswordController::class, 'sendLink'])
        ->name('password.email');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])
        ->name('password.update');

    Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware(['auth:sanctum', 'throttle:6,1'])
        ->name('verification.send');

    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['auth:sanctum', 'signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::get('devices', [AuthController::class, 'devices'])->name('devices');
        Route::delete('devices/{id}', [AuthController::class, 'logoutDevice'])->name('devices.logout');
        Route::post('devices/logout-others', [AuthController::class, 'logoutOtherDevices'])->name('devices.logout-others');

        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('password', [ProfileController::class, 'updatePassword'])->name('password.change');
    });

    Route::prefix('social')->group(function () {
        Route::get('{provider}/redirect', [SocialAuthController::class, 'redirect'])
            ->name('social.redirect')
            ->where('provider', 'google|github');

        Route::get('{provider}/callback', [SocialAuthController::class, 'callback'])
            ->name('social.callback')
            ->where('provider', 'google|github');
    });
});
