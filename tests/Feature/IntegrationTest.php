<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Notification::fake();
    Role::create(['name' => 'user', 'guard_name' => 'web']);
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
});

test('Complete System Flow: Register -> Verify -> Login -> Assign Role', function () {
    // 1. Register
    $password = 'password123';
    $registerPayload = [
        'name' => 'Integration User',
        'email' => 'integration@test.com',
        'password' => $password,
        'password_confirmation' => $password,
    ];

    $response = $this->postJson('/api/v1/auth/register', $registerPayload);
    $response->toBeSuccessResponse();

    $user = User::where('email', 'integration@test.com')->first();
    expect($user->email_verified_at)->toBeNull();

    // 2. Verify Email
    $verificationUrl = URL::temporarySignedRoute(
        'api.v1.auth.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    // Verify while logged in as the new user (Sanctum)
    $this->actingAs($user)
        ->getJson($verificationUrl)
        ->toBeSuccessResponse();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    // 3. Login
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'integration@test.com',
        'password' => $password,
    ]);

    $loginResponse->toBeSuccessResponse()
        ->assertJsonStructure(['data' => ['access_token']]);

    // 4. Assign Role (By Admin)
    $admin = loginAsUser(); // Helper creates verified admin by default if role is assigned
    Permission::create(['name' => 'user.edit', 'guard_name' => 'web']);
    $admin->givePermissionTo('user.edit');

    $this->actingAs($admin)
        ->putJson("/api/v1/users/{$user->id}/roles", [
            'roles' => ['editor'],
        ])->toBeSuccessResponse();

    expect($user->fresh()->hasRole('editor'))->toBeTrue();
})->group('v1', 'integration');
