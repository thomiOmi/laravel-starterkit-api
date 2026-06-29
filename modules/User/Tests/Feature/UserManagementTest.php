<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->admin = loginAsUser();
    Permission::create(['name' => 'user.view', 'guard_name' => 'web']);
    Permission::create(['name' => 'user.create', 'guard_name' => 'web']);
    Permission::create(['name' => 'user.edit', 'guard_name' => 'web']);
    Permission::create(['name' => 'user.delete', 'guard_name' => 'web']);
    $this->admin->givePermissionTo(['user.view', 'user.create', 'user.edit', 'user.delete']);
});

describe('User Listing', function () {
    it('lists users with filters', function () {
        User::factory()->create(['name' => 'Specific User']);

        $this->getJson('/api/v1/users?search=Specific')
            ->toBeSuccessResponse()
            ->assertJsonPath('data.0.name', 'Specific User');
    })->group('v1');
});

describe('User CRUD', function () {
    it('creates a user', function () {
        $this->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'new@user.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->toBeSuccessResponse(status: 201);

        expect(User::where('email', 'new@user.com')->exists())->toBeTrue();
    })->group('v1');

    it('updates a user', function () {
        $user = User::factory()->create();

        $this->putJson("/api/v1/users/{$user->id}", ['name' => 'Updated'])
            ->toBeSuccessResponse();

        expect($user->fresh()->name)->toBe('Updated');
    })->group('v1');

    it('deletes a user', function () {
        $user = User::factory()->create();

        $this->deleteJson("/api/v1/users/{$user->id}")
            ->toBeSuccessResponse();

        expect($user->fresh()->trashed())->toBeTrue();
    })->group('v1');
});
