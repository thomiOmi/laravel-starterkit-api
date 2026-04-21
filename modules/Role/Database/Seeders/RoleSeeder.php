<?php

namespace Modules\Role\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;
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

        // Assign roles to users if they exist
        $this->assignRolesToExistingUsers();
    }

    /**
     * Assign roles to existing users.
     */
    private function assignRolesToExistingUsers(): void
    {
        $superAdmin = User::where('email', 'superadmin@example.com')->first();
        if ($superAdmin) {
            $superAdmin->assignRole('super-admin');
        }

        $admin = User::where('email', 'admin@example.com')->first();
        if ($admin) {
            $admin->assignRole('admin');
        }

        $user = User::where('email', 'user@example.com')->first();
        if ($user) {
            $user->assignRole('user');
        }

        // Assign 'user' role to all other users that don't have a role
        User::all()->each(function ($u) {
            if ($u->roles()->count() === 0) {
                $u->assignRole('user');
            }
        });
    }
}
