<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;

beforeEach(function () {
    Notification::fake();
    Role::create(['name' => 'user', 'guard_name' => 'web']);
});

describe('Authentication Core (Registration Guarding)', function () {
    it('registers a new user as unverified by default', function () {
        $password = config('auth.default_password');
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

        // Check verification notification was sent
        Notification::assertSentTo($user, VerifyEmail::class);
    })->group('v1');

    it('logs in a user but remains restricted if unverified', function () {
        $password = 'secret';
        $user = User::factory()->create(['password' => $password, 'email_verified_at' => null]);

        expect($this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'test-device',
        ]))->toBeSuccessResponse();

        // Should be able to get profile (unprotected by verified middleware)
        // but not access main features (protected by verified middleware)
        expect($this->actingAs($user)
            ->getJson('/api/v1/users'))
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});

describe('Profile & Session', function () {
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
