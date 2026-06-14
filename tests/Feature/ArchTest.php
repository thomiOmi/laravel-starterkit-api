<?php

declare(strict_types=1);

test('globals')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

test('controllers')
    ->expect('Modules\*\Controllers')
    ->toBeFinal()
    ->toBeReadonly()
    ->not->toUse('Illuminate\Database\Eloquent\Model');

test('actions')
    ->expect('Modules\*\Actions')
    ->toBeFinal()
    ->toBeReadonly();

test('repositories')
    ->expect('Modules\*\Repositories')
    ->toBeFinal()
    ->toBeReadonly();

test('strict types')
    ->expect(['App', 'Modules'])
    ->toUseStrictTypes();
