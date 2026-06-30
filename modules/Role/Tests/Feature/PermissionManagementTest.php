<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Modules\Role\Models\Permission;

beforeEach(function () {
    $this->admin = loginAsUser();
    Permission::create(['name' => 'permission.view', 'guard_name' => 'web']);
    Permission::create(['name' => 'permission.create', 'guard_name' => 'web']);
    Permission::create(['name' => 'permission.edit', 'guard_name' => 'web']);
    Permission::create(['name' => 'permission.delete', 'guard_name' => 'web']);
    $this->admin->givePermissionTo(['permission.view', 'permission.create', 'permission.edit', 'permission.delete']);
});

describe('Permission Listing & Filtering', function () {
    it('can list all permissions with pagination', function () {
        $response = $this->getJson('/api/v1/permissions');

        $response->toBeSuccessResponse()
            ->toBePaginated();
    })->group('v1');

    it('can filter permissions by search term', function () {
        Permission::create(['name' => 'post.create', 'guard_name' => 'web']);

        $response = $this->getJson('/api/v1/permissions?search=post');

        expect($response->json('data.0.name'))->toBe('post.create');
    })->group('v1');
});

describe('Permission Lifecycle', function () {
    it('creates a new permission', function () {
        $payload = [
            'name' => 'comment.delete',
            'guard_name' => 'web',
        ];

        $response = $this->postJson('/api/v1/permissions', $payload);

        $response->toBeSuccessResponse(status: 201);
        expect(Permission::where('name', 'comment.delete')->exists())->toBeTrue();
    })->group('v1');

    it('shows permission details', function () {
        $perm = Permission::create(['name' => 'user.block', 'guard_name' => 'web']);

        $response = $this->getJson("/api/v1/permissions/{$perm->id}");

        $response->toBeSuccessResponse()
            ->assertJsonPath('data.id', $perm->id)
            ->assertJsonPath('data.name', 'user.block');
    })->group('v1');

    it('updates an existing permission', function () {
        $perm = Permission::create(['name' => 'old.perm', 'guard_name' => 'web']);

        $response = $this->putJson("/api/v1/permissions/{$perm->id}", [
            'name' => 'new.perm',
        ]);

        $response->toBeSuccessResponse();
        expect($perm->fresh()->name)->toBe('new.perm');
    })->group('v1');

    it('deletes a permission', function () {
        $perm = Permission::create(['name' => 'to.delete', 'guard_name' => 'web']);

        $response = $this->deleteJson("/api/v1/permissions/{$perm->id}");

        $response->toBeSuccessResponse();
        expect(Permission::where('id', $perm->id)->exists())->toBeFalse();
    })->group('v1');
});
