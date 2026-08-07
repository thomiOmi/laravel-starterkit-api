<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Testing\PendingCommand;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response;

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
 *
 * @param  array<int, string>  $abilities
 */
function loginAsUser(?User $user = null, array $abilities = ['*']): User
{
    $authenticatedUser = $user ?? UserFactory::new()->createOne(['email_verified_at' => now()]);

    Sanctum::actingAs($authenticatedUser, $abilities);

    return $authenticatedUser;
}

/**
 * Authenticate an unverified user with Sanctum.
 *
 * @param  array<int, string>  $abilities
 */
function loginAsUnverifiedUser(?User $user = null, array $abilities = ['*']): User
{
    $authenticatedUser = $user ?? UserFactory::new()->createOne(['email_verified_at' => null]);

    Sanctum::actingAs($authenticatedUser, $abilities);

    return $authenticatedUser;
}

/**
 * Authenticate a verified user and assign the given role.
 *
 * The role must already exist in the database (seed `IAMSeeder` in the test).
 *
 * @param  array<int, string>  $abilities
 */
function loginAsRole(RoleEnum $role, ?User $user = null, array $abilities = ['*']): User
{
    $authenticatedUser = loginAsUser($user, $abilities);

    $authenticatedUser->assignRole($role->value);

    return $authenticatedUser;
}

/**
 * Authenticate a verified super-admin user.
 *
 * @param  array<int, string>  $abilities
 */
function loginAsSuperAdmin(?User $user = null, array $abilities = ['*']): User
{
    return loginAsRole(RoleEnum::SuperAdmin, $user, $abilities);
}

/**
 * Authenticate a verified admin user.
 *
 * @param  array<int, string>  $abilities
 */
function loginAsAdmin(?User $user = null, array $abilities = ['*']): User
{
    return loginAsRole(RoleEnum::Admin, $user, $abilities);
}

/**
 * Authenticate a verified user with the basic "user" role.
 *
 * @param  array<int, string>  $abilities
 */
function loginAsUserRole(?User $user = null, array $abilities = ['*']): User
{
    return loginAsRole(RoleEnum::User, $user, $abilities);
}

/**
 * Decode a JSON response body into an associative array.
 *
 * @return array<mixed>
 */
function responseData(Response $response): array
{
    $decoded = json_decode($response->getContent() ?: '{}', true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Assert the response matches the RFC 9457 problem details shape.
 *
 * @param  TestResponse<Response>  $response
 * @param  string|null  $type  Expected "type" value (contains match).
 * @return TestResponse<Response>
 */
function assertProblemResponse(TestResponse $response, int $status = 422, ?string $type = null): TestResponse
{
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

    return $response;
}

/**
 * Assert the response matches the API success envelope.
 *
 * @param  TestResponse<Response>  $response
 * @param  string|null  $title  Expected "title" value (exact match).
 * @return TestResponse<Response>
 */
function assertSuccessResponse(TestResponse $response, int $status = 200, ?string $title = null): TestResponse
{
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

    return $response;
}

/**
 * Assert the response is a paginated success envelope.
 *
 * @param  TestResponse<Response>  $response
 * @return TestResponse<Response>
 */
function assertPaginatedResponse(TestResponse $response): TestResponse
{
    $response->assertJsonStructure(['status', 'data', 'meta']);

    $meta = $response->json('meta');
    expect($meta)
        ->toBeArray()
        ->toHaveKeys(['per_page', 'has_more']);

    return $response;
}

/**
 * Assert the response carries the X-Trace-ID header.
 *
 * @param  TestResponse<Response>  $response
 * @return TestResponse<Response>
 */
function assertHasTraceId(TestResponse $response): TestResponse
{
    $response->assertHeader('X-Trace-ID');
    expect($response->headers->get('X-Trace-ID'))->not->toBeEmpty();

    return $response;
}

/**
 * Assert the response carries the Sunset header in RFC 7231 format.
 *
 * @param  TestResponse<Response>  $response
 * @return TestResponse<Response>
 */
function assertSunsetHeader(TestResponse $response, string $date): TestResponse
{
    $response->assertHeader('Sunset');
    expect($response->headers->get('Sunset'))->toBe(new DateTimeImmutable($date)->format(DateTimeInterface::RFC7231));

    return $response;
}

/**
 * Run an artisan command and return its pending command for assertions.
 *
 * @param  array<string, mixed>  $parameters
 */
function artisanCommand(TestCase $test, string $command, array $parameters = []): PendingCommand
{
    $pending = $test->artisan($command, $parameters);

    if (! $pending instanceof PendingCommand) {
        throw new LogicException('Mocked console output must be enabled to assert on the pending command.');
    }

    return $pending;
}
