<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

beforeEach(function () {
    Notification::fake();
    Role::create(['name' => 'user', 'guard_name' => 'web']);
});

describe('Authentication Core (Registration Guarding)', function () {
    it('registers a new user as unverified by default', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        $password = config('auth.default_password');
        $payload = [
            'name' => 'Unverified User',
            'email' => 'new@auth.com',
            'password' => $password,
            'password_confirmation' => $password,
            'device_name' => 'test-device',
        ];

        $this->postJson('/api/v1/auth/register', $payload)
            ->toBeSuccessResponse();

        $user = User::where('email', 'new@auth.com')->first();
        expect($user->email_verified_at)->toBeNull()
            ->and($user->hasVerifiedEmail())->toBeFalse();

        // Check verification notification was sent
        Notification::assertSentTo($user, VerifyEmail::class);
    })->group('v1');

    it('logs in a user but remains restricted if unverified', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        $password = 'secret';
        $user = User::factory()->create(['password' => $password, 'email_verified_at' => null]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'test-device',
        ])->toBeSuccessResponse();

        // Should be able to get profile (unprotected by verified middleware)
        // but not access main features (protected by verified middleware)
        $this->actingAs($user)
            ->getJson('/api/v1/users')
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});

describe('Profile & Session', function () {
    it('gets the current user profile', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        $user = loginAsUser();

        $this->getJson('/api/v1/auth/me')
            ->toBeSuccessResponse()
            ->assertJsonPath('data.id', $user->id);
    })->group('v1');

    it('logs out successfully', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        $user = loginAsUser();
        $this->postJson('/api/v1/auth/logout')->toBeSuccessResponse();
    })->group('v1');
});
