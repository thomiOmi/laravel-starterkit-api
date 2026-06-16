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

Route::prefix('auth')->middleware(['force.json'])->group(function () {
    // Public routes — strict throttling
    Route::post('register', RegisterController::class)->middleware('throttle:auth')->name('register');
    Route::post('login', LoginController::class)->middleware('throttle:auth')->name('login');
    Route::post('forgot-password', ForgotPasswordController::class)->middleware('throttle:auth')->name('password.forgot');
    Route::post('reset-password', ResetPasswordController::class)->middleware('throttle:auth')->name('password.reset');

    // Email verification — signed URL, safe to use api throttle
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:api'])
        ->name('verification.verify');

    // Social login — redirect and callback
    Route::get('social/{provider}/redirect', SocialRedirectController::class)->middleware('throttle:api')->name('social.redirect');
    Route::get('social/{provider}/callback', SocialCallbackController::class)->middleware('throttle:api')->name('social.callback');

    // Protected routes — higher limit for authenticated users
    Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function () {
        Route::post('logout', LogoutController::class)->name('logout');
        Route::get('me', MeController::class)->name('me');
        Route::post('email/verification-notification', ResendVerificationController::class)
            ->name('verification.send');

        // Device management
        Route::get('devices', ListDevicesController::class)->name('devices.index');
        Route::delete('devices/{device}', DeleteDeviceController::class)->name('devices.delete');
        Route::post('devices/logout-others', LogoutOtherDevicesController::class)->name('devices.logout-others');
    });
});
