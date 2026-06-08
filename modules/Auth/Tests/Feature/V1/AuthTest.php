<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('Auth Core Features V1', function () {
    it('can register a new user', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id', 'name', 'email',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    });

    it('assigns default user role on registration', function () {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'jane@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->hasRole('user'))->toBeTrue();
    });

    it('can login with valid credentials', function () {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user',
                    'access_token',
                    'token_type',
                ],
            ]);
    });

    it('rejects login with invalid credentials', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'errors' => ['email'],
            ]);
    });

    it('can logout', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['full-access'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => __('auth.logout_success')]);
    });

    it('can get authenticated user profile', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['full-access'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/auth/me');

        $response->assertSuccessful()
            ->assertJsonPath('data.email', $user->email);
    });

    it('denies access to unauthenticated users on me endpoint', function () {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

    it('denies access to unauthenticated users on logout endpoint', function () {
        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

    it('can verify email via signed URL', function () {
        $user = User::factory()->unverified()->create();

        $this->assertFalse($user->hasVerifiedEmail());

        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->getJson($url);

        $response->assertSuccessful()
            ->assertJson(['message' => __('auth.verified')]);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    });

    it('rejects email verification with invalid hash', function () {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => 'invalid-hash',
            ]
        );

        $this->getJson($url)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });
});
