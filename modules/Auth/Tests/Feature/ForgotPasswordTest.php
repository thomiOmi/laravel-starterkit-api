<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can request password reset link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertStatus(200);

    Notification::assertSentTo(
        $user,
        ResetPassword::class
    );
});

test('user can reset password with valid token', function () {
    $user = User::factory()->create([
        'password' => bcrypt('old-password'),
    ]);

    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Your password has been reset.');

    $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
});

test('forgot password fails with non-existent email', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('reset password fails with invalid token', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('reset password fails with mismatched passwords', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password123',
        'password_confirmation' => 'mismatched-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});
