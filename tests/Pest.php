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
    ->in('Feature', '../modules/*/Tests/Feature');

pest()
    ->extend(TestCase::class)
    ->in('Unit', '../modules/*/Tests/Unit', 'Architecture');

pest()->beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

expect()->pipe('toMatchSnapshot', function (Closure $next) {
    if (is_string($this->value)) {
        $this->value = preg_replace(
            '/"timestamp":"[^"]+"/',
            '"timestamp":"[dynamic]"',
            $this->value,
        );
    }

    return $next();
});
