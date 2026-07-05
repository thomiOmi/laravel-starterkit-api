<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Modules\IAM\Models\User;
use Pest\Expectation;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../modules/*/Tests/Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Unit', '../modules/*/Tests/Unit');

pest()->extend(TestCase::class)
    ->in('Architecture');

/*
|--------------------------------------------------------------------------
| Global Hooks
|--------------------------------------------------------------------------
|
| Pest allows you to define hooks that will be run before and after each test, as well as
| before and after each test suite. Hooks can be defined globally or locally to a specific
| test file.
*/

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    /** @var Expectation<mixed> $this */
    return $this->toBe(1);
});

expect()->extend('toBeProblemResponse', function (int $status = 422, ?string $type = null): Expectation {
    /** @var Expectation<TestResponse> $this */
    $response = $this->value;

    $response->assertHeader('Content-Type', 'application/problem+json')
        ->assertStatus($status)
        ->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'timestamp',
        ]);

    if ($type !== null) {
        $typeValue = $response->json('type');
        expect(is_string($typeValue) ? $typeValue : '')->toContain($type);
    }

    return $this;
});

expect()->extend('toBeSuccessResponse', function (int $status = 200, ?string $title = null): Expectation {
    /** @var Expectation<TestResponse> $this */
    $response = $this->value;

    $response->assertStatus($status);

    if ($status >= 200 && $status < 300 && $status !== 204 && $status !== 205) {
        $response->assertJsonStructure([
            'status',
            'title',
            'detail',
            'data',
        ]);

        if ($title !== null) {
            expect($response->json('title'))->toBe($title);
        }
    }

    return $this;
});

expect()->extend('toBePaginated', function () {
    /** @var Expectation<mixed> $this */
    /** @var TestResponse $response */
    $response = $this->value;

    $response->assertJsonStructure(['status', 'data', 'meta']);

    $meta = $response->json('meta');
    expect($meta)
        ->toBeArray()
        ->toHaveKeys(['per_page', 'has_more']);

    return $this;
});

expect()->extend('toHaveTraceId', function () {
    /** @var Expectation<mixed> $this */
    /** @var TestResponse $response */
    $response = $this->value;

    $response->assertHeader('X-Trace-ID');
    expect($response->headers->get('X-Trace-ID'))->not->toBeEmpty();

    return $this;
});

expect()->extend('toHaveSunsetHeader', function (string $date) {
    /** @var Expectation<mixed> $this */
    /** @var TestResponse $response */
    $response = $this->value;

    $response->assertHeader('Sunset');
    expect($response->headers->get('Sunset'))->toBe((new DateTimeImmutable($date))->format(DateTimeInterface::RFC7231));

    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Authenticate the given user with Sanctum.
 * By default creates a verified user.
 */
function loginAsUser(?User $user = null, array $abilities = ['*']): User
{
    /** @var User $authenticatedUser */
    $authenticatedUser = $user ?? User::factory()->create(['email_verified_at' => now()]);

    Sanctum::actingAs($authenticatedUser, $abilities);

    return $authenticatedUser;
}

/**
 * Authenticate an unverified user with Sanctum.
 */
function loginAsUnverifiedUser(?User $user = null, array $abilities = ['*']): User
{
    /** @var User $authenticatedUser */
    $authenticatedUser = $user ?? User::factory()->create(['email_verified_at' => null]);

    Sanctum::actingAs($authenticatedUser, $abilities);

    return $authenticatedUser;
}
