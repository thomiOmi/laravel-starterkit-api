<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can register', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'access_token',
                'token_type',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

test('user can login', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
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

test('login fails with wrong credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('user can get profile', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJsonPath('data.email', $user->email);
});

test('user can verify email', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'api.v1.auth.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    $response = $this->getJson($url);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Email verified successfully');

    $this->assertTrue($user->fresh()->hasVerifiedEmail());
});

test('auth routes are rate limited', function () {
    // This is hard to test accurately in feature tests without complex mocks,
    // but we can at least ensure the middleware is present in routes
    $route = collect(Route::getRoutes())->filter(function ($route) {
        return $route->uri() == 'api/v1/auth/login';
    })->first();

    expect($route->middleware())->toContain('throttle:auth');
});
