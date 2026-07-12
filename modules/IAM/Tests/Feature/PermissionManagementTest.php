<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use App\Enums\PermissionEnum;
use Modules\IAM\Models\Permission;

beforeEach(function () {
    $this->admin = loginAsUser();
    Permission::firstOrCreate(['name' => PermissionEnum::PermissionView->value, 'guard_name' => 'sanctum']);
    Permission::firstOrCreate(['name' => PermissionEnum::PermissionCreate->value, 'guard_name' => 'sanctum']);
    Permission::firstOrCreate(['name' => PermissionEnum::PermissionEdit->value, 'guard_name' => 'sanctum']);
    Permission::firstOrCreate(['name' => PermissionEnum::PermissionDelete->value, 'guard_name' => 'sanctum']);
    $this->admin->givePermissionTo([
        PermissionEnum::PermissionView,
        PermissionEnum::PermissionCreate,
        PermissionEnum::PermissionEdit,
        PermissionEnum::PermissionDelete,
    ]);
});

describe('Permission Listing & Filtering', function () {
    it('can list all permissions with pagination', function () {
        $response = $this->getJson('/api/v1/permissions');

        expect($response)->toBeSuccessResponse()
            ->toBePaginated();
    })->group('v1');

    it('can filter permissions by search term', function () {
        Permission::firstOrCreate(['name' => 'post.create', 'guard_name' => 'sanctum']);

        $response = $this->getJson('/api/v1/permissions?search=post');

        expect($response->json('data.0.name'))->toBe('post.create');
    })->group('v1');
});

describe('Permission Lifecycle', function () {
    it('creates a new permission', function () {
        $response = $this->postJson('/api/v1/permissions', [
            'name' => 'comment.delete',
        ]);

        expect($response)->toBeSuccessResponse(status: 201);
        expect(Permission::where('name', 'comment.delete')->exists())->toBeTrue();
    })->group('v1');

    it('fails creating a duplicate permission', function () {
        Permission::firstOrCreate(['name' => 'duplicate.perm', 'guard_name' => 'sanctum']);

        expect($this->postJson('/api/v1/permissions', [
            'name' => 'duplicate.perm',
        ]))->toBeProblemResponse(status: 422);
    })->group('v1');

    it('returns 404 for a non-existent permission', function () {
        expect($this->getJson('/api/v1/permissions/999999'))
            ->toBeProblemResponse(status: 404);
    })->group('v1');

    it('shows permission details', function () {
        $perm = Permission::firstOrCreate(['name' => 'user.block', 'guard_name' => 'sanctum']);

        $response = $this->getJson("/api/v1/permissions/{$perm->id}");

        expect($response)->toBeSuccessResponse()
            ->assertJsonPath('data.id', $perm->id)
            ->assertJsonPath('data.name', 'user.block');
    })->group('v1');

    it('updates an existing permission', function () {
        $perm = Permission::firstOrCreate(['name' => 'old.perm', 'guard_name' => 'sanctum']);

        $response = $this->putJson("/api/v1/permissions/{$perm->id}", [
            'name' => 'new.perm',
        ]);

        expect($response)->toBeSuccessResponse();
        expect($perm->fresh()->name)->toBe('new.perm');
    })->group('v1');

    it('returns 404 when updating a non-existent permission', function () {
        expect($this->putJson('/api/v1/permissions/999999', [
            'name' => 'ghost',
        ]))->toBeProblemResponse(status: 404);
    })->group('v1');

    it('deletes a permission', function () {
        $perm = Permission::firstOrCreate(['name' => 'to.delete', 'guard_name' => 'sanctum']);

        $response = $this->deleteJson("/api/v1/permissions/{$perm->id}");

        expect($response)->toBeSuccessResponse(status: 204);
        expect(Permission::where('id', $perm->id)->exists())->toBeFalse();
    })->group('v1');

    it('returns 404 when deleting a non-existent permission', function () {
        expect($this->deleteJson('/api/v1/permissions/999999'))
            ->toBeProblemResponse(status: 404);
    });
});
