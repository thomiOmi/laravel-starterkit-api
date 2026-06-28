<?php

declare(strict_types=1);

test('strict types are used')
    ->expect(['App\\', 'Modules\\', 'Tests\\'])
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

test('models should not be used in form requests')
    ->expect('Modules\*\Models')
    ->not->toBeUsedIn('Modules\*\Requests');

test('models should not be used in payloads')
    ->expect('Modules\*\Models')
    ->not->toBeUsedIn('Modules\*\Payloads');

test('actions should not use request directly')
    ->expect('Illuminate\Http\Request')
    ->not->toBeUsedIn('Modules\*\Actions');

test('avoid debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
