<?php

declare(strict_types=1);

arch('app and tests use strict types', function () {
    $basePath = config('architecture.module.base_path', base_path('modules'));
    $moduleNs = ucfirst(basename($basePath));

    expect([$moduleNs, 'App', 'Tests'])
        ->toUseStrictTypes();
});

arch('avoid debugging functions', function () {
    expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
        ->not->toBeUsed();
});

arch('avoid env() outside of config files', function () {
    expect('env')
        ->not->toBeUsed()
        ->ignoring('config');
});

arch('tests should not use PHPUnit assertions', function () {
    expect('Tests')
        ->not->toUse('PHPUnit\Framework\Assert');
});

arch('controllers should be final, readonly, and invokable', function () {
    $moduleNs = ucfirst(basename(config('architecture.module.base_path', base_path('modules'))));

    expect($moduleNs.'\*\Controllers')
        ->toBeFinal()
        ->toBeReadonly()
        ->not->toUse('Illuminate\Database\Eloquent\Model')
        ->toHaveMethod('__invoke');
});

arch('actions should be final, readonly, and have handle', function () {
    $moduleNs = ucfirst(basename(config('architecture.module.base_path', base_path('modules'))));

    expect($moduleNs.'\*\Actions')
        ->toBeFinal()
        ->toBeReadonly()
        ->toHaveMethod('handle');
});

arch('payloads should be final and readonly', function () {
    $moduleNs = ucfirst(basename(config('architecture.module.base_path', base_path('modules'))));

    expect($moduleNs.'\*\Payloads')
        ->toBeFinal()
        ->toBeReadonly();
});

arch('models should not be used in form requests', function () {
    $moduleNs = ucfirst(basename(config('architecture.module.base_path', base_path('modules'))));

    expect($moduleNs.'\*\Models')
        ->not->toBeUsedIn($moduleNs.'\*\Requests');
});

arch('models should not be used in payloads', function () {
    $moduleNs = ucfirst(basename(config('architecture.module.base_path', base_path('modules'))));

    expect($moduleNs.'\*\Models')
        ->not->toBeUsedIn($moduleNs.'\*\Payloads');
});

arch('actions should not use request directly', function () {
    $moduleNs = ucfirst(basename(config('architecture.module.base_path', base_path('modules'))));

    expect('Illuminate\Http\Request')
        ->not->toBeUsedIn($moduleNs.'\*\Actions');
});

arch('modules should be isolated', function () {
    $moduleNs = ucfirst(basename(config('architecture.module.base_path', base_path('modules'))));

    expect($moduleNs)
        ->toOnlyBeUsedIn($moduleNs)
        ->ignoring(['App\Providers', 'Tests', 'App\Console', 'Database\Seeders']);
});
