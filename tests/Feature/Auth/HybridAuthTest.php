<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('stateless (token-based) auth', function () {
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

describe('stateful (session-based) auth', function () {
    it('omits token on stateful login', function () {
        $password = 'Password123!';
        $user = User::factory()->create(['password' => Hash::make($password)]);

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withHeader('Referer', 'http://localhost:3000')
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => $password,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'title',
            'detail',
            'data' => ['user'],
        ]);
        $response->assertJsonMissingPath('data.access_token');
        $response->assertJsonMissingPath('data.token_type');
    });

    it('authenticates web guard on stateful login', function () {
        $password = 'Password123!';
        $user = User::factory()->create(['password' => Hash::make($password)]);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withHeader('Referer', 'http://localhost:3000')
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => $password,
            ]);

        expect(auth()->guard('web')->check())->toBeTrue();
    });

    it('omits token on stateful register', function () {
        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withHeader('Referer', 'http://localhost:3000')
            ->postJson('/api/v1/auth/register', [
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
            'data' => ['user'],
        ]);
        $response->assertJsonMissingPath('data.access_token');
        $response->assertJsonMissingPath('data.token_type');
    });

    it('authenticates web guard on stateful register', function () {
        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withHeader('Referer', 'http://localhost:3000')
            ->postJson('/api/v1/auth/register', [
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        expect(auth()->guard('web')->check())->toBeTrue();
    });
});

describe('device_name override', function () {
    it('returns token even with stateful referer when device_name is provided', function () {
        $password = 'Password123!';
        $user = User::factory()->create(['password' => Hash::make($password)]);

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withHeader('Referer', 'http://localhost:3000')
            ->postJson('/api/v1/auth/login', [
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

    it('does not establish session when device_name overrides stateful', function () {
        $password = 'Password123!';
        $user = User::factory()->create(['password' => Hash::make($password)]);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withHeader('Referer', 'http://localhost:3000')
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => $password,
                'device_name' => 'Postman',
            ]);

        expect(auth()->guard('web')->check())->toBeFalse();
    });
});
