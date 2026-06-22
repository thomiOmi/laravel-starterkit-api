<?php

declare(strict_types=1);

use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('Auth Core Features V1', function () {
    it('can register a new user', function () {
        $password = config('auth.default_password');
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'title',
                'detail',
                'data' => [
                    'user',
                    'access_token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    });

    it('can login with valid credentials', function () {
        $password = config('auth.default_password');
        $user = User::factory()->create([
            'password' => $password,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'title',
                'detail',
                'data' => [
                    'user',
                    'access_token',
                    'token_type',
                ],
            ]);
    });

    it('can logout', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['auth:manage'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(Response::HTTP_NO_CONTENT);
    });

    it('can get authenticated user profile', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['users:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/auth/me');

        $response->assertSuccessful()
            ->assertJsonPath('data.email', $user->email);
    });

    it('can logout other devices', function () {
        $password = config('auth.default_password');
        $user = User::factory()->create([
            'password' => $password,
        ]);
        $user->assignRole('user');

        $currentToken = $user->createToken('current', ['auth:manage'])->plainTextToken;
        $user->createToken('other-device', ['auth:manage']);

        $this->assertCount(2, $user->tokens);

        $response = $this->withHeader('Authorization', "Bearer $currentToken")
            ->postJson('/api/v1/auth/devices/logout-others', [
                'current_password' => $password,
            ]);

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $user->refresh();
        $this->assertCount(1, $user->tokens);
    });

    it('rejects request without required ability', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['users:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('rejects logout-others without current password', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['auth:manage'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/devices/logout-others', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('current_password');
    });
});
