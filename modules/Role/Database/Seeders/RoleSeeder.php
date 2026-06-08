<?php

declare(strict_types=1);

namespace Modules\Role\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Role\Models\Permission;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guards = ['web'];
        $permissions = [
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
            'user.restore',
            'role.view',
            'role.create',
            'role.edit',
            'role.delete',
            'role.restore',
        ];

        foreach ($guards as $guard) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
            }

            Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => $guard]);

            $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
            $admin->givePermissionTo($permissions);

            $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => $guard]);
            $user->givePermissionTo(['user.view']);
        }

        // Assign roles to users if they exist
        $this->assignRolesToExistingUsers();
    }

    /**
     * Assign roles to existing users.
     */
    private function assignRolesToExistingUsers(): void
    {
        $superAdmin = User::with('roles')->where('email', 'superadmin@example.com')->first();
        if ($superAdmin) {
            $superAdmin->assignRole('super-admin');
        }

        $admin = User::with('roles')->where('email', 'admin@example.com')->first();
        if ($admin) {
            $admin->assignRole('admin');
        }

        $user = User::with('roles')->where('email', 'user@example.com')->first();
        if ($user) {
            $user->assignRole('user');
        }

        // Assign 'user' role to all other users that don't have a role
        User::with('roles')->get()->each(function ($u) {
            if ($u->roles->count() === 0) {
                $u->assignRole('user');
            }
        });
    }
}
