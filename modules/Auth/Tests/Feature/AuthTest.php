<?php

declare(strict_types=1);

use App\Notifications\VerifyEmail;
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

test('registration fails with missing fields', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('registration fails with duplicate email', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
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

test('email verification fails with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'api.v1.auth.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => 'invalid-hash']
    );

    $response = $this->getJson($url);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['hash']);
});

test('email verification fails with expired link', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'api.v1.auth.verification.verify',
        now()->subMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    $response = $this->getJson($url);

    $response->assertStatus(403); // Signed middleware returns 403 for expired links
});

test('user can resend verification email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->postJson('/api/v1/auth/email/verify/resend');

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Verification link sent');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('user cannot resend verification email if already verified', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->postJson('/api/v1/auth/email/verify/resend');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('auth routes are rate limited', function () {
    // This is hard to test accurately in feature tests without complex mocks,
    // but we can at least ensure the middleware is present in routes
    $route = collect(Route::getRoutes())->filter(function ($route) {
        return $route->uri() == 'api/v1/auth/login';
    })->first();

    expect($route->middleware())->toContain('throttle:auth');
});
