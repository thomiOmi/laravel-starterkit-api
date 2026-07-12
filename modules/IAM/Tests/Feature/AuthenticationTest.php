<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use App\Enums\RoleEnum;
use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;

beforeEach(function () {
    Notification::fake();
    Role::create(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
});

describe('Authentication Core (Registration Guarding)', function () {
    $password = 'test-password';

    it('fails registration with duplicate email', function () use ($password) {
        User::factory()->create(['email' => 'dup@auth.com']);

        expect($this->postJson('/api/v1/auth/register', [
            'name' => 'Duplicate User',
            'email' => 'dup@auth.com',
            'password' => $password,
            'password_confirmation' => $password,
        ]))->toBeProblemResponse(status: 422);
    })->group('v1');

    it('registers a new user as unverified by default', function () use ($password) {
        $payload = [
            'name' => 'Unverified User',
            'email' => 'new@auth.com',
            'password' => $password,
            'password_confirmation' => $password,
            'device_name' => 'test-device',
        ];

        expect($this->postJson('/api/v1/auth/register', $payload))
            ->toBeSuccessResponse(status: 201);

        $user = User::where('email', 'new@auth.com')->first();
        expect($user->email_verified_at)->toBeNull()
            ->and($user->hasVerifiedEmail())->toBeFalse();

        Notification::assertSentTo($user, VerifyEmail::class);
    })->group('v1');

    it('fails login with invalid credentials', function () {
        $password = 'secret';
        User::factory()->create(['password' => Hash::make($password)]);

        expect($this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@email.com',
            'password' => $password,
            'device_name' => 'test-device',
        ]))->toBeProblemResponse(status: 422);
    })->group('v1');

    it('logs in a user but remains restricted if unverified', function () {
        $password = 'secret';
        $user = User::factory()->create(['password' => Hash::make($password), 'email_verified_at' => null]);

        expect($this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'test-device',
        ]))->toBeSuccessResponse();

        expect($this->actingAs($user)
            ->getJson('/api/v1/users'))
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});

describe('Profile & Session', function () {
    it('denies profile access when unauthenticated', function () {
        expect($this->getJson('/api/v1/auth/me'))
            ->toBeProblemResponse(status: 401);
    })->group('v1');

    it('gets the current user profile', function () {
        $user = loginAsUser();

        expect($this->getJson('/api/v1/auth/me'))
            ->toBeSuccessResponse()
            ->assertJsonPath('data.id', $user->id);
    })->group('v1');

    it('logs out successfully', function () {
        $user = loginAsUser();
        expect($this->postJson('/api/v1/auth/logout'))->toBeSuccessResponse(status: 204);
    })->group('v1');
});
