<?php

declare(strict_types=1);

use Laravel\Sanctum\Sanctum;
use Modules\IAM\Models\User;

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
    $authenticatedUser = $user ?? User::factory()->create(['email_verified_at' => now()]);

    Sanctum::actingAs($authenticatedUser, $abilities);

    return $authenticatedUser;
}

/**
 * Authenticate an unverified user with Sanctum.
 */
function loginAsUnverifiedUser(?User $user = null, array $abilities = ['*']): User
{
    $authenticatedUser = $user ?? User::factory()->create(['email_verified_at' => null]);

    Sanctum::actingAs($authenticatedUser, $abilities);

    return $authenticatedUser;
}
