<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->tenant = Tenant::create(['id' => 'test-tenant']);
    tenancy()->initialize($this->tenant);
});

describe('Registration', function () {
    it('can register a new user', function () {
        $response = $this->withHeader('X-Tenant', 'test-tenant')
            ->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'tenant_id' => 'test-tenant',
        ]);
    });
});

describe('Authentication', function () {
    it('can login with valid credentials', function () {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'tenant_id' => 'test-tenant',
        ]);

        $response = $this->withHeader('X-Tenant', 'test-tenant')
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'password123',
            ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user',
                    'access_token',
                    'token_type',
                ],
            ]);
    });

    it('can logout', function () {
        $user = User::factory()->create(['tenant_id' => 'test-tenant']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('X-Tenant', 'test-tenant')
            ->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        $response->assertSuccessful();
    });
});

describe('Profile', function () {
    it('can get authenticated user profile', function () {
        $user = User::factory()->create(['tenant_id' => 'test-tenant']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('X-Tenant', 'test-tenant')
            ->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/auth/me');

        $response->assertSuccessful()
            ->assertJsonPath('data.email', $user->email);
    });
});

describe('Email Verification', function () {
    it('can verify email', function () {
        $user = User::factory()->unverified()->create(['tenant_id' => 'test-tenant']);

        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('X-Tenant', 'test-tenant')
            ->withHeader('Authorization', "Bearer $token")
            ->getJson($url);

        $response->assertSuccessful();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    });

    it('can resend verification email', function () {
        Notification::fake();

        $user = User::factory()->unverified()->create(['tenant_id' => 'test-tenant']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('X-Tenant', 'test-tenant')
            ->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/email/verification-notification');

        $response->assertSuccessful();
    });
});
