<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Event::fake();
    Notification::fake();

    // Seed essential roles
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'user', 'guard_name' => 'web']);
});

describe('User Registration (SOP)', function () {
    it('assigns default role "user" and denies "admin" by default', function () {
        $password = config('auth.default_password');
        $payload = [
            'name' => 'New Customer',
            'email' => 'customer@test.com',
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->toBeSuccessResponse();

        $user = User::where('email', 'customer@test.com')->first();
        expect($user->hasRole('user'))->toBeTrue()
            ->and($user->hasRole('admin'))->toBeFalse();
    })->group('v1');

    it('guards against mass assignment of sensitive fields (is_admin/role)', function () {
        $password = config('auth.default_password');
        $payload = [
            'name' => 'Hacker',
            'email' => 'hacker@test.com',
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'admin',       // Attempted injection
            'is_admin' => true,      // Attempted injection
            'permissions' => ['*'],  // Attempted injection
        ];

        $this->postJson('/api/v1/auth/register', $payload)
            ->toBeSuccessResponse();

        $user = User::where('email', 'hacker@test.com')->first();
        expect($user->hasRole('admin'))->toBeFalse()
            ->and($user->hasRole('user'))->toBeTrue();
    })->group('v1');
});

describe('Gatekeeper & IDOR Protection', function () {
    it('denies guest access to the profile', function () {
        $this->getJson('/api/v1/auth/me')
            ->toBeProblemResponse(status: 401);
    })->group('v1');

    it('prevents User A from updating User B profile (IDOR)', function () {
        $userA = loginAsUser();
        $userB = User::factory()->create(['name' => 'Safe User']);

        // Assuming PUT /api/v1/users/{id} exists for profile update or admin management
        $this->putJson("/api/v1/users/{$userB->id}", ['name' => 'Modified By A'])
            ->toBeProblemResponse(status: 403);

        expect($userB->fresh()->name)->toBe('Safe User');
    })->group('v1');
});

describe('API Contract & Data Leakage', function () {
    it('does not leak password field in the response', function () {
        $user = loginAsUser();
        Permission::create(['name' => 'user.view', 'guard_name' => 'web']);
        $user->givePermissionTo('user.view');

        $response = $this->getJson("/api/v1/users/{$user->id}");

        $response->toBeSuccessResponse()
            ->assertJsonMissing(['password', 'remember_token', 'provider_id'])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email'],
            ]);
    })->group('v1');
});

describe('Self-Deletion Protection', function () {
    it('prevents a user from deleting their own account via API', function () {
        $admin = loginAsUser();
        Permission::create(['name' => 'user.delete', 'guard_name' => 'web']);
        $admin->givePermissionTo('user.delete');

        $this->deleteJson("/api/v1/users/{$admin->id}")
            ->toBeProblemResponse(status: 403);

        expect($admin->fresh()->trashed())->toBeFalse();
    })->group('v1');
});
