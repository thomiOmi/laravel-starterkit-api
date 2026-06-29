<?php

declare(strict_types=1);

arch('app and tests use strict types')
    ->expect(['App', 'Modules', 'Tests'])
    ->toUseStrictTypes();

arch('avoid debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('avoid env() outside of config files')
    ->expect('env')
    ->not->toBeUsed()
    ->ignoring('config');

arch('tests should not use PHPUnit assertions')
    ->expect('Tests')
    ->not->toUse('PHPUnit\Framework\Assert');

arch('controllers should be final, readonly, and invokable')
    ->expect('Modules\*\Controllers')
    ->toBeFinal()
    ->toBeReadonly()
    ->not->toUse('Illuminate\Database\Eloquent\Model')
    ->toHaveMethod('__invoke');

arch('actions should be final, readonly, and have handle')
    ->expect('Modules\*\Actions')
    ->toBeFinal()
    ->toBeReadonly()
    ->toHaveMethod('handle');

arch('repositories should be final and readonly')
    ->expect('Modules\*\Repositories')
    ->toBeFinal()
    ->toBeReadonly();

arch('payloads should be final and readonly')
    ->expect('Modules\*\Payloads')
    ->toBeFinal()
    ->toBeReadonly();

arch('models should not be used in form requests')
    ->expect('Modules\*\Models')
    ->not->toBeUsedIn('Modules\*\Requests');

arch('models should not be used in payloads')
    ->expect('Modules\*\Models')
    ->not->toBeUsedIn('Modules\*\Payloads');

arch('actions should not use request directly')
    ->expect('Illuminate\Http\Request')
    ->not->toBeUsedIn('Modules\*\Actions');
