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
    ->in('../modules/*/tests/Feature');

pest()
    ->extend(TestCase::class)
    ->group('app', 'unit')
    ->in('Unit');

pest()
    ->extend(TestCase::class)
    ->group('unit')
    ->in('../modules/*/tests/Unit');

pest()
    ->extend(TestCase::class)
    ->group('arch')
    ->in('Architecture');

pest()->beforeEach(function (): void {
    // Prevent BaseQueryBuilder::reportWarning from writing to storage/logs/laravel.log
    // during the "logs in production" test. The phpunit.xml LOG_CHANNEL=null should
    // already use NullHandler, but ensure the config is correctly set for the test
    // environment where app()->detectEnvironment('production') is used.
    config(['logging.default' => 'null']);
    app()->forgetInstance('log');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});
