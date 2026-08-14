<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->group('app', 'feature')
    ->in('Feature');

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->group('feature')
    ->in('../modules/*/Tests/Feature');

pest()
    ->extend(TestCase::class)
    ->group('app', 'unit')
    ->in('Unit');

pest()
    ->extend(TestCase::class)
    ->group('unit')
    ->in('../modules/*/Tests/Unit');

pest()
    ->extend(TestCase::class)
    ->group('arch')
    ->in('Architecture');

pest()->beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});
