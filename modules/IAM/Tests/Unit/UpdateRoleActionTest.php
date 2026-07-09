<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\UpdateRoleAction;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Payloads\V1\RolePayload;

describe('UpdateRoleAction', function () {
    it('updates an existing role name', function () {
        $role = Role::create(['name' => 'old-role', 'guard_name' => 'sanctum']);
        $action = app(UpdateRoleAction::class);

        $result = $action->handle($role->id, new RolePayload(
            name: 'new-role',
        ));

        expect($result)->toBeInstanceOf(Role::class)
            ->name->toBe('new-role');
    });

    it('syncs permissions when provided', function () {
        $perm = Permission::create(['name' => 'role.perm', 'guard_name' => 'sanctum']);
        $role = Role::create(['name' => 'perm-role', 'guard_name' => 'sanctum']);
        $action = app(UpdateRoleAction::class);

        $result = $action->handle($role->id, new RolePayload(
            name: 'perm-role',
            permissions: [$perm->name],
        ));

        expect($result->hasPermissionTo('role.perm'))->toBeTrue();
    });

    it('returns null for a non-existent role', function () {
        $action = app(UpdateRoleAction::class);

        $result = $action->handle('999999', new RolePayload(
            name: 'ghost',
        ));

        expect($result)->toBeNull();
    });
});
