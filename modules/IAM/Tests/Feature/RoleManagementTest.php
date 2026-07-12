<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use App\Enums\PermissionEnum;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

beforeEach(function () {
    $this->admin = loginAsUser();
    // Ensure admin has required permissions
    Permission::firstOrCreate(['name' => PermissionEnum::RoleView->value, 'guard_name' => 'sanctum']);
    Permission::firstOrCreate(['name' => PermissionEnum::RoleCreate->value, 'guard_name' => 'sanctum']);
    Permission::firstOrCreate(['name' => PermissionEnum::RoleEdit->value, 'guard_name' => 'sanctum']);
    Permission::firstOrCreate(['name' => PermissionEnum::RoleDelete->value, 'guard_name' => 'sanctum']);
    $this->admin->givePermissionTo([
        PermissionEnum::RoleView,
        PermissionEnum::RoleCreate,
        PermissionEnum::RoleEdit,
        PermissionEnum::RoleDelete,
    ]);
});

describe('Role Listing & Filtering', function () {
    it('can list all roles with pagination', function () {
        Role::create(['name' => 'manager', 'guard_name' => 'sanctum']);

        $response = $this->getJson('/api/v1/roles');

        expect($response)->toBeSuccessResponse()
            ->toBePaginated();

        expect(collect($response->json('data'))->pluck('name'))->toContain('manager');
    })->group('v1');

    it('can filter roles by search term', function () {
        Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'viewer', 'guard_name' => 'sanctum']);

        $response = $this->getJson('/api/v1/roles?search=editor');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('editor');
    })->group('v1');
});

describe('Role Lifecycle', function () {
    it('fails creating a duplicate role name', function () {
        Role::create(['name' => 'duplicate', 'guard_name' => 'sanctum']);

        expect($this->postJson('/api/v1/roles', [
            'name' => 'duplicate',
        ]))->toBeProblemResponse(status: 422);
    })->group('v1');

    it('returns 404 for a non-existent role', function () {
        expect($this->getJson('/api/v1/roles/999999'))
            ->toBeProblemResponse(status: 404);
    })->group('v1');

    it('creates a new role with permissions', function () {
        $perm = Permission::firstOrCreate(['name' => 'post.view', 'guard_name' => 'sanctum']);

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
        $role = Role::create(['name' => 'support', 'guard_name' => 'sanctum']);

        $response = $this->getJson("/api/v1/roles/{$role->id}");

        expect($response)->toBeSuccessResponse()
            ->assertJsonPath('data.id', $role->id)
            ->assertJsonPath('data.name', 'support');
    })->group('v1');

    it('updates an existing role', function () {
        $role = Role::create(['name' => 'old-name', 'guard_name' => 'sanctum']);

        $response = $this->putJson("/api/v1/roles/{$role->id}", [
            'name' => 'new-name',
        ]);

        expect($response)->toBeSuccessResponse();
        expect($role->fresh()->name)->toBe('new-name');
    })->group('v1');

    it('returns 404 when updating a non-existent role', function () {
        expect($this->putJson('/api/v1/roles/999999', [
            'name' => 'ghost',
        ]))->toBeProblemResponse(status: 404);
    })->group('v1');

    it('deletes a role', function () {
        $role = Role::create(['name' => 'to-be-deleted', 'guard_name' => 'sanctum']);

        $response = $this->deleteJson("/api/v1/roles/{$role->id}");

        expect($response)->toBeSuccessResponse(status: 204);
        expect($role->fresh())->toBeNull();
    })->group('v1');

    it('returns 404 when deleting a non-existent role', function () {
        expect($this->deleteJson('/api/v1/roles/999999'))
            ->toBeProblemResponse(status: 404);
    })->group('v1');
});
