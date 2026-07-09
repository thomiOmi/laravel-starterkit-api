<?php

declare(strict_types=1);

namespace Modules\IAM\Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        /** @var PermissionRegistrar $registrar */
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $guards = ['sanctum'];
        $permissions = PermissionEnum::cases();

        foreach ($guards as $guard) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission->value, 'guard_name' => $guard]);
            }

            Role::firstOrCreate(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => $guard]);

            $admin = Role::firstOrCreate(['name' => RoleEnum::Admin->value, 'guard_name' => $guard]);
            $admin->givePermissionTo($permissions);

            $user = Role::firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => $guard]);
            $user->givePermissionTo([PermissionEnum::UserView->value]);
        }

        // Assign roles to users if they exist
        $this->assignRolesToExistingUsers();
    }

    /**
     * Assign roles to existing users.
     */
    private function assignRolesToExistingUsers(): void
    {
        $roleMap = [
            'superadmin@example.com' => RoleEnum::SuperAdmin,
            'admin@example.com' => RoleEnum::Admin,
            'user@example.com' => RoleEnum::User,
        ];

        User::with('roles')
            ->whereIn('email', array_keys($roleMap))
            ->get()
            ->each(function (User $user) use ($roleMap) {
                $user->assignRole($roleMap[$user->email]->value);
            });

        // Assign 'user' role to all other users that don't have a role
        /** @var Builder<User> $query */
        $query = User::whereDoesntHave('roles');
        $query
            ->with('roles')
            ->chunkById(100, function (Collection $users) {
                foreach ($users as $user) {
                    $user->assignRole(RoleEnum::User->value);
                }
            });
    }
}
