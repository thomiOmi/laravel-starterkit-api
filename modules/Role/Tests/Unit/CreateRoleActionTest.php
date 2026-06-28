<?php

declare(strict_types=1);

use Modules\Role\Actions\CreateRoleAction;
use Modules\Role\Models\Permission;
use Modules\Role\Models\Role;
use Modules\Role\Payloads\V1\RolePayload;

describe('CreateRoleAction', function () {
    it('creates a role', function () {
        $payload = new RolePayload(name: 'manager', permissions: []);

        $action = app(CreateRoleAction::class);
        $role = $action->handle($payload);

        expect($role)->toBeInstanceOf(Role::class)
            ->name->toBe('manager');
    });

    it('syncs permissions when provided', function () {
        $permission = Permission::create(['name' => 'manager.view', 'guard_name' => 'web']);
        $payload = new RolePayload(name: 'manager', permissions: ['manager.view']);

        $action = app(CreateRoleAction::class);
        $role = $action->handle($payload);

        expect($role->hasPermissionTo('manager.view'))->toBeTrue();
    });
});
