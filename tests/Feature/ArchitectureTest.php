<?php

declare(strict_types=1);

arch('strict types are used')
    ->expect('App\\')
    ->toUseStrictTypes()
    ->and('Modules\\')
    ->toUseStrictTypes();

arch('controllers should not use models directly')
    ->expect('Modules\**\Controllers')
    ->not->toUse('Illuminate\Database\Eloquent\Model')
    ->not->toUse('Modules\**\Models');

arch('dtos should be readonly')
    ->expect('App\DTOs')
    ->toBeReadonly()
    ->and('Modules\*\DTOs')
    ->toBeReadonly();

arch('avoid debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
