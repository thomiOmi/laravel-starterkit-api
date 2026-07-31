<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Testing\PendingCommand;
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
