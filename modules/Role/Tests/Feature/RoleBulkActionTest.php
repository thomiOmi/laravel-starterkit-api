<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Modules\Role\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->admin = loginAsUser();
    Permission::create(['name' => 'role.edit', 'guard_name' => 'web']);
    Permission::create(['name' => 'role.delete', 'guard_name' => 'web']);
    $this->admin->givePermissionTo(['role.edit', 'role.delete']);
});

describe('Role Bulk Operations', function () {
    it('can bulk delete roles', function () {
        $roles = [
            Role::create(['name' => 'r1', 'guard_name' => 'web']),
            Role::create(['name' => 'r2', 'guard_name' => 'web'])
        ];
        $ids = collect($roles)->pluck('id')->toArray();

        $this->postJson('/api/v1/roles/bulk/delete', ['ids' => $ids])
            ->toBeSuccessResponse();

        foreach ($roles as $role) {
            expect($role->fresh()->trashed())->toBeTrue();
        }
    })->group('v1');

    it('can bulk restore roles', function () {
        $roles = [
            Role::create(['name' => 'r1', 'guard_name' => 'web']),
            Role::create(['name' => 'r2', 'guard_name' => 'web'])
        ];
        $ids = collect($roles)->pluck('id')->toArray();
        Role::whereIn('id', $ids)->delete();

        $this->postJson('/api/v1/roles/bulk/restore', ['ids' => $ids])
            ->toBeSuccessResponse();

        foreach ($roles as $role) {
            expect($role->fresh()->trashed())->toBeFalse();
        }
    })->group('v1');
});
