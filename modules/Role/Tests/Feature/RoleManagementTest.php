<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Modules\Role\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->admin = loginAsUser();
    Permission::create(['name' => 'role.view', 'guard_name' => 'web']);
    Permission::create(['name' => 'role.create', 'guard_name' => 'web']);
    Permission::create(['name' => 'role.edit', 'guard_name' => 'web']);
    Permission::create(['name' => 'role.delete', 'guard_name' => 'web']);
    $this->admin->givePermissionTo(['role.view', 'role.create', 'role.edit', 'role.delete']);
});

describe('Role Management', function () {
    it('lists roles', function () {
        $this->getJson('/api/v1/roles')
            ->toBeSuccessResponse()
            ->toBePaginated();
    })->group('v1');

    it('creates a new role', function () {
        $this->postJson('/api/v1/roles', [
            'name' => 'super-moderator',
            'guard_name' => 'web',
        ])->toBeSuccessResponse(status: 201);

        expect(Role::where('name', 'super-moderator')->exists())->toBeTrue();
    })->group('v1');
});
