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
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../modules/*/Tests/Feature');

pest()->extend(TestCase::class)
    ->in('Unit', '../modules/*/Tests/Unit');

pest()->extend(TestCase::class)
    ->in('Architecture');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
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
*/

/**
 * Authenticate the given user with Sanctum.
 */
function loginAsUser(?User $user = null, array $abilities = ['*']): User
{
    /** @var User $authenticatedUser */
    $authenticatedUser = $user ?? User::factory()->create();

    Sanctum::actingAs($authenticatedUser, $abilities);

    return $authenticatedUser;
}
