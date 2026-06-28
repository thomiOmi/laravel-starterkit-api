<?php

declare(strict_types=1);

use Modules\Role\Database\Factories\PermissionFactory;
use Modules\Role\Models\Permission;
use Modules\Role\Models\Role;

describe('PermissionFactory', function () {
    it('creates a permission with factory', function () {
        $permission = PermissionFactory::new()->create();

        expect($permission)->toBeInstanceOf(Permission::class)
            ->id->not->toBeNull()
            ->guard_name->toBe('web');
    });

    it('creates a permission with specific name', function () {
        $permission = PermissionFactory::new()->create(['name' => 'custom.permission']);

        expect($permission->name)->toBe('custom.permission');
    });

    it('creates unique permission names', function () {
        $perm1 = PermissionFactory::new()->create(['name' => 'perm.one']);
        $perm2 = PermissionFactory::new()->create(['name' => 'perm.two']);

        expect($perm1->name)->not->toBe($perm2->name);
    });
});

describe('Permission Spatie Integration', function () {
    it('can be assigned to a role', function () {
        $permission = PermissionFactory::new()->create();
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $permission->assignRole($role);

        expect($role->hasPermissionTo($permission->name))->toBeTrue();
    });

    it('can be removed from a role', function () {
        $permission = PermissionFactory::new()->create();
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $permission->assignRole($role);
        $permission->removeRole($role);

        expect($role->hasPermissionTo($permission->name))->toBeFalse();
    });

    it('can sync multiple roles', function () {
        $permission = PermissionFactory::new()->create();
        $role1 = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $role2 = Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $permission->syncRoles([$role1->name, $role2->name]);

        expect(Role::permission($permission->name)->count())->toBe(2);
    });
});
