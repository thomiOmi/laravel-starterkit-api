<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

beforeEach(function () {
    $this->admin = loginAsUser();
    Permission::firstOrCreate(['name' => 'role.edit', 'guard_name' => 'sanctum']);
    Permission::firstOrCreate(['name' => 'role.delete', 'guard_name' => 'sanctum']);
    $this->admin->givePermissionTo(['role.edit', 'role.delete']);
});

describe('Role Bulk Operations', function () {
    it('fails bulk delete with missing ids', function () {
        expect($this->postJson('/api/v1/roles/bulk/delete', []))
            ->toBeProblemResponse(status: 422);
    })->group('v1');

    it('can bulk delete roles', function () {
        $roles = [
            Role::create(['name' => 'r1', 'guard_name' => 'web']),
            Role::create(['name' => 'r2', 'guard_name' => 'web']),
        ];
        $ids = collect($roles)->pluck('id')->toArray();

        expect($this->postJson('/api/v1/roles/bulk/delete', ['ids' => $ids]))
            ->toBeSuccessResponse();

        foreach ($roles as $role) {
            expect($role->fresh()->trashed())->toBeTrue();
        }
    })->group('v1');

    it('can bulk restore roles', function () {
        $roles = [
            Role::create(['name' => 'r1', 'guard_name' => 'web']),
            Role::create(['name' => 'r2', 'guard_name' => 'web']),
        ];
        $ids = collect($roles)->pluck('id')->toArray();
        Role::whereIn('id', $ids)->delete();

        expect($this->postJson('/api/v1/roles/bulk/restore', ['ids' => $ids]))
            ->toBeSuccessResponse();

        foreach ($roles as $role) {
            expect($role->fresh()->trashed())->toBeFalse();
        }
    })->group('v1');
});
