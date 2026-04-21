<?php

namespace Modules\Role\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for User module
        Permission::firstOrCreate(['name' => 'user.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'user.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'user.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'user.delete', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'user.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'user.create', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'user.edit', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'user.delete', 'guard_name' => 'sanctum']);

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdminSanctum = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'sanctum']);
        // Super admin usually gets all permissions via a gate in AuthServiceProvider or similar

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
        ]);

        $adminSanctum = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $adminSanctum->givePermissionTo([
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
        ]);

        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->givePermissionTo([
            'user.view',
        ]);

        $userSanctum = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
        $userSanctum->givePermissionTo([
            'user.view',
        ]);
    }
}
