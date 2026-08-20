<?php

declare(strict_types=1);

namespace Modules\IAM\Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed the IAM module: permissions, roles, and users in a single run.
 *
 * Order is intentional (see Spatie laravel-permission seeding docs):
 * flush cache -> permissions -> roles -> users (roles assigned via
 * factory states) -> flush cache. Single-seeder design avoids the
 * cross-seeder race of creating users in one seeder and assigning
 * roles to them from another.
 */
class IAMSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->forgetCachedPermissions();

        $this->seedPermissionsAndRoles();

        $this->seedUsers();

        $this->forgetCachedPermissions();
    }

    /**
     * Flush the cached roles and permissions.
     */
    private function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Create permissions and roles for the sanctum guard.
     */
    private function seedPermissionsAndRoles(): void
    {
        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate(['name' => $permission->value, 'guard_name' => 'sanctum']);
        }

        Role::firstOrCreate(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);

        $admin = Role::firstOrCreate(['name' => RoleEnum::Admin->value, 'guard_name' => 'sanctum']);
        $admin->givePermissionTo(PermissionEnum::cases());

        $user = Role::firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
        $user->givePermissionTo([PermissionEnum::UserView->value]);
    }

    /**
     * Create the default users with their roles via factory states.
     */
    private function seedUsers(): void
    {
        $rawPassword = config()->string('auth.default_password');
        $password = Hash::make(filled($rawPassword) ? $rawPassword : Str::random(32));

        UserFactory::new()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => $password,
        ]);

        UserFactory::new()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => $password,
        ]);

        UserFactory::new()->user()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => $password,
        ]);

        UserFactory::new()->user()->unverified()->create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => $password,
        ]);

        UserFactory::new()->user()->count(9)->create();
    }
}
