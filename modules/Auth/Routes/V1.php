<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\V1\DeleteDeviceController;
use Modules\Auth\Controllers\V1\ForgotPasswordController;
use Modules\Auth\Controllers\V1\ListDevicesController;
use Modules\Auth\Controllers\V1\LoginController;
use Modules\Auth\Controllers\V1\LogoutController;
use Modules\Auth\Controllers\V1\LogoutOtherDevicesController;
use Modules\Auth\Controllers\V1\MeController;
use Modules\Auth\Controllers\V1\RegisterController;
use Modules\Auth\Controllers\V1\ResendVerificationController;
use Modules\Auth\Controllers\V1\ResetPasswordController;
use Modules\Auth\Controllers\V1\SocialCallbackController;
use Modules\Auth\Controllers\V1\SocialRedirectController;
use Modules\Auth\Controllers\V1\VerifyEmailController;

Route::prefix('auth')->middleware(['force.json', 'throttle:api'])->group(function () {
    // Public routes
    Route::post('register', RegisterController::class)->name('register');
    Route::post('login', LoginController::class)->name('login');
    Route::post('forgot-password', ForgotPasswordController::class)->name('password.forgot');
    Route::post('reset-password', ResetPasswordController::class)->name('password.reset');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed'])
        ->name('verification.verify');

    // Social login routes
    Route::get('social/{provider}/redirect', SocialRedirectController::class)->name('social.redirect');
    Route::get('social/{provider}/callback', SocialCallbackController::class)->name('social.callback');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', LogoutController::class)->name('logout');
        Route::get('me', MeController::class)->name('me');
        Route::post('email/verification-notification', ResendVerificationController::class)
            ->middleware(['throttle:6,1'])
            ->name('verification.send');

        // Device management
        Route::get('devices', ListDevicesController::class)->name('devices.index');
        Route::delete('devices/{device}', DeleteDeviceController::class)->name('devices.destroy');
        Route::post('devices/logout-others', LogoutOtherDevicesController::class)->name('devices.logout-others');
    });
});
