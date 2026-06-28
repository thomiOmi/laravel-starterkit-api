<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('token-based auth', function () {
    it('returns token on login', function () {
        $password = 'Password123!';
        $user = User::factory()->create(['password' => Hash::make($password)]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'title',
            'detail',
            'data' => ['user', 'access_token', 'token_type'],
        ]);
        expect($response->json('data.access_token'))->toBeString();
    });

    it('returns token on register', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'status',
            'title',
            'detail',
            'data' => ['user', 'access_token', 'token_type'],
        ]);
        expect($response->json('data.access_token'))->toBeString();
    });
});

describe('device_name field', function () {
    it('accepts device_name and returns token', function () {
        $password = 'Password123!';
        $user = User::factory()->create(['password' => Hash::make($password)]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'Postman',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'title',
            'detail',
            'data' => ['user', 'access_token', 'token_type'],
        ]);
        expect($response->json('data.access_token'))->toBeString();
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
