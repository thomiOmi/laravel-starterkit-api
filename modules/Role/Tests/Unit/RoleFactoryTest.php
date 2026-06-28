<?php

declare(strict_types=1);

use Modules\Role\Database\Factories\RoleFactory;
use Modules\Role\Models\Permission;
use Modules\Role\Models\Role;

describe('RoleFactory', function () {
    it('creates a role with factory', function () {
        $role = RoleFactory::new()->create();

        expect($role)->toBeInstanceOf(Role::class)
            ->id->not->toBeNull()
            ->guard_name->toBe('web');
    });

    it('creates a role with specific name', function () {
        $role = RoleFactory::new()->create(['name' => 'editor']);

        expect($role->name)->toBe('editor');
    });

    it('creates unique role names', function () {
        $role1 = RoleFactory::new()->create(['name' => 'editor']);
        $role2 = RoleFactory::new()->create(['name' => 'admin']);

        expect($role1->name)->not->toBe($role2->name);
    });
});

describe('Role Spatie Integration', function () {
    it('can be assigned permissions', function () {
        $role = RoleFactory::new()->create();
        $permission = Permission::create(['name' => 'test.permission', 'guard_name' => 'web']);

        $role->givePermissionTo($permission);

        expect($role->hasPermissionTo('test.permission'))->toBeTrue();
    });

    it('can sync multiple permissions', function () {
        $role = RoleFactory::new()->create();
        $permission1 = Permission::create(['name' => 'test.view', 'guard_name' => 'web']);
        $permission2 = Permission::create(['name' => 'test.edit', 'guard_name' => 'web']);

        $role->syncPermissions([$permission1->name, $permission2->name]);

        expect($role->hasPermissionTo('test.view'))->toBeTrue()
            ->and($role->hasPermissionTo('test.edit'))->toBeTrue();
    });

    it('can be revoked permissions', function () {
        $role = RoleFactory::new()->create();
        $permission = Permission::create(['name' => 'test.permission', 'guard_name' => 'web']);

        $role->givePermissionTo($permission);
        $role->revokePermissionTo($permission);

        expect($role->hasPermissionTo('test.permission'))->toBeFalse();
    });
});
