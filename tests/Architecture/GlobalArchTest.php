<?php

declare(strict_types=1);

test('globals')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

test('controllers')
    ->expect('Modules\*\Controllers')
    ->toBeFinal()
    ->toBeReadonly()
    ->toHaveMethod('__invoke');

test('actions')
    ->expect('Modules\*\Actions')
    ->toBeFinal()
    ->toBeReadonly()
    ->toHaveMethod('handle');

test('strict types')
    ->expect(['App', 'Modules'])
    ->toUseStrictTypes();

test('models should not be used in controllers')
    ->expect('Modules\*\Models')
    ->not->toBeUsedIn('Modules\*\Controllers');

test('actions should not use request directly')
    ->expect('Illuminate\Http\Request')
    ->not->toBeUsedIn('Modules\*\Actions');
