<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Modules\Role\Models\Permission;
use Modules\Role\Models\Role;

beforeEach(function () {
    $this->admin = loginAsUser();
    // Ensure admin has required permissions
    Permission::create(['name' => 'role.view', 'guard_name' => 'sanctum']);
    Permission::create(['name' => 'role.create', 'guard_name' => 'sanctum']);
    Permission::create(['name' => 'role.edit', 'guard_name' => 'sanctum']);
    Permission::create(['name' => 'role.delete', 'guard_name' => 'sanctum']);
    $this->admin->givePermissionTo(['role.view', 'role.create', 'role.edit', 'role.delete']);
});

describe('Role Listing & Filtering', function () {
    it('can list all roles with pagination', function () {
        Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $response = $this->getJson('/api/v1/roles');

        expect($response)->toBeSuccessResponse()
            ->toBePaginated();

        expect(collect($response->json('data'))->pluck('name'))->toContain('manager');
    })->group('v1');

    it('can filter roles by search term', function () {
        Role::create(['name' => 'editor', 'guard_name' => 'web']);
        Role::create(['name' => 'viewer', 'guard_name' => 'web']);

        $response = $this->getJson('/api/v1/roles?search=editor');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('editor');
    })->group('v1');
});

describe('Role Lifecycle', function () {
    it('creates a new role with permissions', function () {
        $perm = Permission::create(['name' => 'post.view', 'guard_name' => 'sanctum']);

        $payload = [
            'name' => 'author',
            'permissions' => [$perm->name],
        ];

        $response = $this->postJson('/api/v1/roles', $payload);

        expect($response)->toBeSuccessResponse(status: 201);

        expect(Role::where('name', 'author')->exists())->toBeTrue()
            ->and(Role::findByName('author')->hasPermissionTo('post.view'))->toBeTrue();
    })->group('v1');

    it('shows role details', function () {
        $role = Role::create(['name' => 'support', 'guard_name' => 'web']);

        $response = $this->getJson("/api/v1/roles/{$role->id}");

        expect($response)->toBeSuccessResponse()
            ->assertJsonPath('data.id', $role->id)
            ->assertJsonPath('data.name', 'support');
    })->group('v1');

    it('updates an existing role', function () {
        $role = Role::create(['name' => 'old-name', 'guard_name' => 'web']);

        $response = $this->putJson("/api/v1/roles/{$role->id}", [
            'name' => 'new-name',
        ]);

        expect($response)->toBeSuccessResponse();
        expect($role->fresh()->name)->toBe('new-name');
    })->group('v1');

    it('soft deletes a role', function () {
        $role = Role::create(['name' => 'to-be-deleted', 'guard_name' => 'web']);

        $response = $this->deleteJson("/api/v1/roles/{$role->id}");

        expect($response)->toBeSuccessResponse(status: 204);
        expect($role->fresh()->trashed())->toBeTrue();
    })->group('v1');
});
