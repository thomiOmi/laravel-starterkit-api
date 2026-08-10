<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;
use Modules\IAM\Controllers\V1\ChangePasswordController;
use Modules\IAM\Controllers\V1\DeleteAccountController;
use Modules\IAM\Controllers\V1\DeleteDeviceController;
use Modules\IAM\Controllers\V1\DeviceListController;
use Modules\IAM\Controllers\V1\ForgotPasswordController;
use Modules\IAM\Controllers\V1\LoginController;
use Modules\IAM\Controllers\V1\LogoutController;
use Modules\IAM\Controllers\V1\LogoutOtherDevicesController;
use Modules\IAM\Controllers\V1\MeController;
use Modules\IAM\Controllers\V1\PermissionCreateController;
use Modules\IAM\Controllers\V1\PermissionDeleteController;
use Modules\IAM\Controllers\V1\PermissionListController;
use Modules\IAM\Controllers\V1\PermissionShowController;
use Modules\IAM\Controllers\V1\PermissionUpdateController;
use Modules\IAM\Controllers\V1\RegisterController;
use Modules\IAM\Controllers\V1\ResendVerificationController;
use Modules\IAM\Controllers\V1\ResetPasswordController;
use Modules\IAM\Controllers\V1\RoleBulkDeleteController;
use Modules\IAM\Controllers\V1\RoleCreateController;
use Modules\IAM\Controllers\V1\RoleDeleteController;
use Modules\IAM\Controllers\V1\RoleListController;
use Modules\IAM\Controllers\V1\RoleShowController;
use Modules\IAM\Controllers\V1\RoleUpdateController;
use Modules\IAM\Controllers\V1\SocialCallbackController;
use Modules\IAM\Controllers\V1\SocialLinkController;
use Modules\IAM\Controllers\V1\SocialRedirectController;
use Modules\IAM\Controllers\V1\SocialUnlinkController;
use Modules\IAM\Controllers\V1\UpdateProfileController;
use Modules\IAM\Controllers\V1\UserAssignRolesController;
use Modules\IAM\Controllers\V1\UserBulkDeleteController;
use Modules\IAM\Controllers\V1\UserBulkRestoreController;
use Modules\IAM\Controllers\V1\UserCreateController;
use Modules\IAM\Controllers\V1\UserDeleteController;
use Modules\IAM\Controllers\V1\UserListController;
use Modules\IAM\Controllers\V1\UserShowController;
use Modules\IAM\Controllers\V1\UserUpdateController;
use Modules\IAM\Controllers\V1\VerifyEmailController;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('login', LoginController::class)->middleware('throttle:auth')->name('login');
    Route::post('register', RegisterController::class)
        ->middleware(['feature.flag:iam.self-registration', 'throttle:auth', 'idempotency'])
        ->name('register');
    Route::post('forgot-password', ForgotPasswordController::class)->middleware('throttle:auth')->name('password.forgot');
    Route::post('reset-password', ResetPasswordController::class)->middleware('throttle:auth')->name('password.reset');

    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['auth:sanctum', 'signed', 'throttle:api'])
        ->name('verification.verify');

    Route::get('social/{provider}/redirect', SocialRedirectController::class)->middleware('throttle:api')->name('social.redirect');
    Route::get('social/{provider}/callback', SocialCallbackController::class)->middleware('throttle:api')->name('social.callback');

    Route::middleware(['auth:sanctum', 'active', 'throttle:authenticated'])->group(function () {
        Route::post('email/verification-notification', ResendVerificationController::class)
            ->middleware('ability:users:write')
            ->name('verification.send');

        Route::post('change-password', ChangePasswordController::class)
            ->name('password.change');

        Route::delete('account', DeleteAccountController::class)
            ->name('account.delete');

        Route::middleware('verified')->group(function () {
            Route::post('logout', LogoutController::class)->middleware('ability:auth:manage')->name('logout');
            Route::get('me', MeController::class)->middleware('ability:users:read')->name('me');
            Route::put('me', UpdateProfileController::class)->name('me.update');

            Route::get('social/{provider}/link', SocialLinkController::class)->name('social.link');
            Route::delete('social/{provider}', SocialUnlinkController::class)->name('social.unlink');

            Route::get('devices', DeviceListController::class)->middleware('ability:auth:manage')->name('devices.index');
            Route::delete('devices/{device}', DeleteDeviceController::class)->middleware('ability:auth:manage')->name('devices.delete');
            Route::post('devices/logout-others', LogoutOtherDevicesController::class)->middleware('ability:auth:manage')->name('devices.logout-others');
        })->whereUlid(['device']);
    });
});

Route::prefix('users')->name('user.')->middleware(['auth:sanctum', 'active', 'verified', 'throttle:api'])->group(function () {
    Route::get('/', UserListController::class)->middleware('permission:'.PermissionEnum::UserView->value)->name('index');
    Route::post('/', UserCreateController::class)->middleware('permission:'.PermissionEnum::UserCreate->value)->name('create');

    Route::post('/bulk/delete', UserBulkDeleteController::class)->middleware('permission:'.PermissionEnum::UserDelete->value)->name('bulk.delete');
    Route::post('/bulk/restore', UserBulkRestoreController::class)->middleware('permission:'.PermissionEnum::UserRestore->value)->name('bulk.restore');

    Route::get('/{user}', UserShowController::class)->name('show');
    Route::put('/{user}', UserUpdateController::class)->name('update');
    Route::put('/{user}/roles', UserAssignRolesController::class)->middleware('permission:'.PermissionEnum::UserEdit->value)->name('roles.assign');
    Route::delete('/{user}', UserDeleteController::class)->middleware('permission:'.PermissionEnum::UserDelete->value)->name('delete');
})->whereUlid(['user']);

Route::prefix('roles')->name('role.')->middleware(['auth:sanctum', 'active', 'verified', 'throttle:api'])->group(function () {
    Route::get('/', RoleListController::class)->middleware('permission:'.PermissionEnum::RoleView->value)->name('index');
    Route::post('/', RoleCreateController::class)->middleware('permission:'.PermissionEnum::RoleCreate->value)->name('create');

    Route::post('/bulk/delete', RoleBulkDeleteController::class)->middleware('permission:'.PermissionEnum::RoleDelete->value)->name('bulk.delete');

    Route::get('/{role}', RoleShowController::class)->middleware('permission:'.PermissionEnum::RoleView->value)->name('show');
    Route::put('/{role}', RoleUpdateController::class)->middleware('permission:'.PermissionEnum::RoleEdit->value)->name('update');
    Route::delete('/{role}', RoleDeleteController::class)->middleware('permission:'.PermissionEnum::RoleDelete->value)->name('delete');
})->whereUlid(['role']);

Route::prefix('permissions')->name('permission.')->middleware(['auth:sanctum', 'active', 'verified', 'throttle:api'])->group(function () {
    Route::get('/', PermissionListController::class)->middleware('permission:'.PermissionEnum::PermissionView->value)->name('index');
    Route::post('/', PermissionCreateController::class)->middleware('permission:'.PermissionEnum::PermissionCreate->value)->name('create');
    Route::get('/{permission}', PermissionShowController::class)->middleware('permission:'.PermissionEnum::PermissionView->value)->name('show');
    Route::put('/{permission}', PermissionUpdateController::class)->middleware('permission:'.PermissionEnum::PermissionEdit->value)->name('update');
    Route::delete('/{permission}', PermissionDeleteController::class)->middleware('permission:'.PermissionEnum::PermissionDelete->value)->name('delete');
})->whereUlid(['permission']);
