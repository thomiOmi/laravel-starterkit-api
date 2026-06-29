<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Modules\User\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'user', 'guard_name' => 'web']);
});

describe('Authentication Core', function () {
    it('registers a new user', function () {
        $password = config('auth.default_password');
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@auth.com',
            'password' => $password,
            'password_confirmation' => $password,
            'device_name' => 'test-device',
        ];

        $this->postJson('/api/v1/auth/register', $payload)
            ->toBeSuccessResponse(status: 200)
            ->toHaveTraceId()
            ->assertJsonPath('data.user.email', 'john@auth.com');

        expect(User::where('email', 'john@auth.com')->exists())->toBeTrue();
    })->group('v1');

    it('logs in a user', function () {
        $password = 'secret';
        $user = User::factory()->create(['password' => $password]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'test-device',
        ])->toBeSuccessResponse()
            ->toHaveTraceId()
            ->assertJsonPath('data.user.id', $user->id);
    })->group('v1');

    it('logs out a user', function () {
        $user = loginAsUser();

        $this->postJson('/api/v1/auth/logout')
            ->toBeSuccessResponse();
    })->group('v1');

    it('gets the current user profile', function () {
        $user = loginAsUser();

        $this->getJson('/api/v1/auth/me')
            ->toBeSuccessResponse()
            ->assertJsonPath('data.id', $user->id);
    })->group('v1');
});
