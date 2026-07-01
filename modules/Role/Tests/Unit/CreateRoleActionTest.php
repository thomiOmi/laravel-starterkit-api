<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Unit;

use Modules\Role\Actions\CreateRoleAction;
use Modules\Role\Models\Permission;
use Modules\Role\Models\Role;
use Modules\Role\Payloads\V1\RolePayload;

/**
 * Unit test for CreateRoleAction.
 */
describe('CreateRoleAction', function () {
    it('creates a role and syncs permissions', function () {
        $permission = Permission::firstOrCreate(['name' => 'test.perm', 'guard_name' => 'web']);
        $action = app(CreateRoleAction::class);

        $role = $action->handle(new RolePayload(
            name: 'new-role',
            permissions: [$permission->name],
        ));

        expect($role)->toBeInstanceOf(Role::class)
            ->name->toBe('new-role');

        expect($role->hasPermissionTo('test.perm'))->toBeTrue();
    });
});
