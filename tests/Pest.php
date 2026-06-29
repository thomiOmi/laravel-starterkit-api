<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;
use Pest\Expectation;
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
    ->in('../modules/*/Tests/Feature');

pest()->extend(TestCase::class)
    ->in('../modules/*/Tests/Unit');

pest()->extend(TestCase::class)
    ->in('Architecture');

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

expect()->extend('toBeProblemResponse', function (int $status = 422, ?string $type = null) {
    /** @var Expectation<mixed> $this */
    /** @var TestResponse $response */
    $response = $this->value;

    $response->assertHeader('Content-Type', 'application/problem+json')
        ->assertStatus($status)
        ->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
        ]);

    if ($type !== null) {
        expect($response->json('type'))->toContain($type);
    }

    return $this;
});

expect()->extend('toBeSuccessResponse', function (int $status = 200, ?string $title = null) {
    /** @var Expectation<mixed> $this */
    /** @var TestResponse $response */
    $response = $this->value;

    $response->assertStatus($status)
        ->assertJsonStructure([
            'status',
            'title',
            'detail',
            'data',
        ]);

    if ($title !== null) {
        expect($response->json('title'))->toBe($title);
    }

    return $this;
});

expect()->extend('toBePaginated', function () {
    /** @var Expectation<mixed> $this */
    /** @var TestResponse $response */
    $response = $this->value;

    $response->assertJsonStructure([
        'data',
        'links' => ['first', 'last', 'prev', 'next'],
        'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
    ]);

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
 *
 * @param  array<int, string>  $abilities
 */
function loginAsUser(?User $user = null, array $abilities = ['*']): User
{
    /** @var User $authenticatedUser */
    $authenticatedUser = $user ?? User::factory()->create();

    Sanctum::actingAs($authenticatedUser, $abilities);

    return $authenticatedUser;
}
