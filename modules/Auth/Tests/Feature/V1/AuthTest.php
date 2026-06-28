<?php

declare(strict_types=1);

use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('Auth Core Features V1', function () {
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
