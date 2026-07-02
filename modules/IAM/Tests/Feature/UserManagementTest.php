<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Event::fake();
    Notification::fake();

    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'user', 'guard_name' => 'web']);
});

describe('Middleware Guarding (Verified Email)', function () {
    it('denies access to user listing if email is not verified', function () {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->assignRole('admin'); // Even an admin must be verified

        expect($this->actingAs($user)
            ->getJson('/api/v1/users'))
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('denies access to create user if email is not verified', function () {
        $user = User::factory()->create(['email_verified_at' => null]);

        expect($this->actingAs($user)
            ->postJson('/api/v1/users', ['name' => 'Test']))
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});

describe('User Registration & Initial State', function () {
    it('assigns default role and unverified state on registration', function () {
        $password = config('auth.default_password');
        $payload = [
            'name' => 'New Customer',
            'email' => 'customer@test.com',
            'password' => $password,
            'password_confirmation' => $password,
        ];

        expect($this->postJson('/api/v1/auth/register', $payload))->toBeSuccessResponse(status: 201);

        $user = User::where('email', 'customer@test.com')->first();
        expect($user->email_verified_at)->toBeNull()
            ->and($user->hasRole('user'))->toBeTrue();
    })->group('v1');
});

describe('User CRUD & IDOR Protection', function () {
    it('denies access if User A updates User B profile', function () {
        $userA = loginAsUser(); // Logged in and verified by helper
        $userB = User::factory()->create(['name' => 'Safe']);

        expect($this->putJson("/api/v1/users/{$userB->id}", ['name' => 'Hacked']))
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('prevents self-deletion', function () {
        $admin = loginAsUser();
        Permission::firstOrCreate(['name' => 'user.delete', 'guard_name' => 'sanctum']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin->givePermissionTo('user.delete');

        expect($this->deleteJson("/api/v1/users/{$admin->id}"))
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});
