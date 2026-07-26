<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;
use Pest\Expectation;

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
