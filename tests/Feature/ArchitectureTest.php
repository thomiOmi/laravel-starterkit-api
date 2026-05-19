<?php

declare(strict_types=1);

arch('strict types are used')
    ->expect('App\\')
    ->toUseStrictTypes()
    ->and('Modules\\')
    ->toUseStrictTypes();

arch('controllers should be final and readonly')
    ->expect('Modules\**\Controllers')
    ->toBeFinal()
    ->toBeReadonly();

arch('actions should be final and readonly')
    ->expect('Modules\**\Actions')
    ->toBeFinal()
    ->toBeReadonly();

arch('controllers should not use models directly')
    ->expect('Modules\**\Controllers')
    ->not->toUse('Illuminate\Database\Eloquent\Model')
    ->not->toUse('Modules\**\Models')
    ->ignoring([
        'Modules\User\Controllers\V1\IndexController',
        'Modules\User\Controllers\V1\ShowController',
        'Modules\User\Controllers\V1\UpdateController',
        'Modules\User\Controllers\V1\DestroyController',
        'Modules\User\Controllers\V1\BulkActionController',
        'Modules\Role\Controllers\V1\IndexController',
        'Modules\Role\Controllers\V1\ShowController',
        'Modules\Role\Controllers\V1\UpdateController',
        'Modules\Role\Controllers\V1\DestroyController',
        'Modules\Role\Controllers\V1\BulkActionController',
        'Modules\Auth\Controllers\V1\MeController',
        'Modules\Auth\Controllers\V1\LogoutController',
    ]);

arch('payloads should be final and readonly')
    ->expect('Modules\*\Payloads')
    ->toBeFinal()
    ->toBeReadonly();

arch('avoid debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
