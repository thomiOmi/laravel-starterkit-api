<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Event::fake();
    Notification::fake();

    // Default permissions for authorized tests
    Permission::create(['name' => 'user.view', 'guard_name' => 'web']);
    Permission::create(['name' => 'user.create', 'guard_name' => 'web']);
    Permission::create(['name' => 'user.edit', 'guard_name' => 'web']);
    Permission::create(['name' => 'user.delete', 'guard_name' => 'web']);
});

describe('User Listing', function () {
    it('allows authorized admin to list users', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.view');
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/users');

        $response->toBeSuccessResponse()
            ->toBePaginated()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'created_at'],
                ],
            ]);
    })->group('v1');

    it('denies user listing if unauthorized', function () {
        loginAsUser(); // Regular user, no permissions

        $this->getJson('/api/v1/users')
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});

describe('User Creation', function () {
    it('allows authorized admin to create a user', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.create');

        $payload = [
            'name' => 'Staff New',
            'email' => 'staff@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->toBeSuccessResponse(status: 201)
            ->assertJsonPath('data.email', 'staff@example.com')
            ->assertJsonMissing(['password']); // Security check: no password leak

        $this->assertDatabaseHas('users', ['email' => 'staff@example.com']);
    })->group('v1');

    it('denies user creation if unauthorized', function () {
        loginAsUser();

        $this->postJson('/api/v1/users', ['name' => 'Illegal'])
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});

describe('User Update & Detail', function () {
    it('shows user details to authorized admin', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.view');
        $user = User::factory()->create();

        $this->getJson("/api/v1/users/{$user->id}")
            ->toBeSuccessResponse()
            ->assertJsonPath('data.id', $user->id);
    })->group('v1');

    it('denies access to other users profile (IDOR Protection)', function () {
        loginAsUser(); // User A
        $userB = User::factory()->create();

        $this->getJson("/api/v1/users/{$userB->id}")
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('updates user data and prevents sensitive field leak', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.edit');
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => 'New Name',
            'email' => $user->email,
        ]);

        $response->toBeSuccessResponse()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonMissing(['password']);

        expect($user->fresh()->name)->toBe('New Name');
    })->group('v1');
});

describe('User Deletion', function () {
    it('allows authorized admin to delete a user', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.delete');
        $user = User::factory()->create();

        $this->deleteJson("/api/v1/users/{$user->id}")
            ->toBeSuccessResponse();

        expect($user->fresh()->trashed())->toBeTrue();
    })->group('v1');

    it('prevents self-deletion', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.delete');

        // Attempting to delete themselves
        $this->deleteJson("/api/v1/users/{$admin->id}")
            ->toBeProblemResponse(status: 403);

        expect($admin->fresh()->trashed())->toBeFalse();
    })->group('v1');
});
