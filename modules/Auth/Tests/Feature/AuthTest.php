<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->password = config('auth.default_password');
});

describe('Login', function () {
    it('authenticates user with valid credentials', function () {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $this->password,
        ])
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json->whereType('status', 'integer')
                ->whereType('title', 'string')
                ->whereType('detail', 'string')
                ->where('data.token_type', 'Bearer')
                ->whereType('data.access_token', 'string')
                ->where('data.user.email', $user->email)
                ->etc()
            );
    });

    it('returns validation error for missing credentials', function () {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('email')
            ->assertJsonValidationErrorFor('password');
    });

    it('returns validation error for invalid email format', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('rejects invalid credentials', function () {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('rejects non-existent email to prevent enumeration', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });
});

describe('Register', function () {
    it('registers a new user', function () {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => $this->password,
            'password_confirmation' => $this->password,
        ])
            ->assertCreated()
            ->assertJson(fn (AssertableJson $json) => $json->whereType('status', 'integer')
                ->where('data.user.name', 'New User')
                ->where('data.user.email', 'newuser@example.com')
                ->where('data.token_type', 'Bearer')
                ->whereType('data.access_token', 'string')
                ->etc()
            );
    });

    it('returns validation error for missing fields', function () {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('name')
            ->assertJsonValidationErrorFor('email')
            ->assertJsonValidationErrorFor('password');
    });

    it('rejects duplicate email', function () {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Duplicate Email',
            'email' => $user->email,
            'password' => $this->password,
            'password_confirmation' => $this->password,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('email');
    });

    it('requires password confirmation', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'No Confirm',
            'email' => 'noconfirm@example.com',
            'password' => $this->password,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('password');
    });

    it('rejects weak password', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Weak Password',
            'email' => 'weak@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });
});

describe('Auth Core Features V1', function () {
    it('can logout', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['auth:manage']);

        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(Response::HTTP_NO_CONTENT);
    });

    it('can get authenticated user profile', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['users:read']);

        $this->getJson('/api/v1/auth/me')
            ->assertSuccessful()
            ->assertJsonPath('data.email', $user->email);
    });

    it('rejects request without required ability', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['users:read']);

        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('rejects logout-others without current password', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['auth:manage']);

        $this->postJson('/api/v1/auth/devices/logout-others', [])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('current_password');
    });
});

describe('device_name field', function () {
    it('accepts device_name and returns token', function () {
        $password = 'Password123!';
        $user = User::factory()->create(['password' => Hash::make($password)]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'Postman',
        ])
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json->where('data.token_type', 'Bearer')
                ->whereType('data.access_token', 'string')
                ->etc()
            );
    });

    it('does not establish web session from login endpoint', function () {
        $password = 'Password123!';
        $user = User::factory()->create(['password' => Hash::make($password)]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        expect(auth()->guard('web')->check())->toBeFalse();
    });
});
