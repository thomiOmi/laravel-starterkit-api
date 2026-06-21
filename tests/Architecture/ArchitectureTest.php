<?php

declare(strict_types=1);

test('strict types are used')
    ->expect(['App\\', 'Modules\\'])
    ->toUseStrictTypes();

test('controllers should be final, readonly, and invokable')
    ->expect('Modules\*\Controllers')
    ->toBeFinal()
    ->toBeReadonly()
    ->not->toUse('Illuminate\Database\Eloquent\Model')
    ->toHaveMethod('__invoke');

test('actions should be final, readonly, and have handle')
    ->expect('Modules\*\Actions')
    ->toBeFinal()
    ->toBeReadonly()
    ->toHaveMethod('handle');

test('repositories should be final and readonly')
    ->expect('Modules\*\Repositories')
    ->toBeFinal()
    ->toBeReadonly();

test('payloads should be final and readonly')
    ->expect('Modules\*\Payloads')
    ->toBeFinal()
    ->toBeReadonly();

test('models should not be used in controllers')
    ->expect('Modules\*\Models')
    ->not->toBeUsedIn('Modules\*\Controllers')
    ->ignoring([
        'Modules\User\Controllers\V1\IndexController',
        'Modules\User\Controllers\V1\BulkDeleteController',
        'Modules\User\Controllers\V1\BulkRestoreController',
        'Modules\Role\Controllers\V1\IndexController',
        'Modules\Role\Controllers\V1\BulkDeleteRolesController',
        'Modules\Role\Controllers\V1\BulkRestoreRolesController',
        'Modules\Auth\Controllers\V1\MeController',
        'Modules\Auth\Controllers\V1\LogoutController',
        'Modules\Auth\Controllers\V1\DeleteDeviceController',
        'Modules\Auth\Controllers\V1\ListDevicesController',
        'Modules\Auth\Controllers\V1\LogoutOtherDevicesController',
        'Modules\Auth\Controllers\V1\ResendVerificationController',
        'Modules\Auth\Controllers\V1\ResetPasswordController',
        'Modules\Auth\Controllers\V1\VerifyEmailController',
    ]);

test('actions should not use request directly')
    ->expect('Illuminate\Http\Request')
    ->not->toBeUsedIn('Modules\*\Actions');

test('avoid debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
