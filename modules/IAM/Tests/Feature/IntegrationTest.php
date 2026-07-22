<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;

beforeEach(function () {
    Notification::fake();
    foreach (['web', 'sanctum'] as $guard) {
        Role::create(['name' => RoleEnum::User->value, 'guard_name' => $guard]);
        Role::create(['name' => 'editor', 'guard_name' => $guard]);
    }
});

test('Complete System Flow: Register -> Verify -> Login -> Assign Role', function () {
    $password = 'password123';
    $registerPayload = [
        'name' => 'Integration User',
        'email' => 'integration@test.com',
        'password' => $password,
        'password_confirmation' => $password,
    ];

    $response = $this->postJson('/api/v1/auth/register', $registerPayload);
    expect($response)->toBeSuccessResponse(status: 201);

    $user = User::where('email', 'integration@test.com')->first();
    expect($user->email_verified_at)->toBeNull();

    $verificationUrl = URL::temporarySignedRoute(
        'v1.auth.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    expect($this->actingAs($user)
        ->getJson($verificationUrl))
        ->toBeSuccessResponse();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'integration@test.com',
        'password' => $password,
    ]);

    expect($loginResponse)->toBeSuccessResponse()
        ->assertJsonStructure(['data' => ['access_token']]);

    $admin = loginAsUser();
    Permission::firstOrCreate(['name' => PermissionEnum::UserEdit->value, 'guard_name' => 'sanctum']);
    $admin->givePermissionTo(PermissionEnum::UserEdit);

    expect($this->actingAs($admin)
        ->putJson("/api/v1/users/{$user->id}/roles", [
            'roles' => ['editor'],
        ]))->toBeSuccessResponse();

    expect($user->fresh()->hasRole('editor'))->toBeTrue();
})->group('v1', 'integration');
