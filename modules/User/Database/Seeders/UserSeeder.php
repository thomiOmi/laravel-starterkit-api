<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAM\Database\Factories\UserFactory;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        UserFactory::new()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => config('auth.default_password'),
        ]);

        UserFactory::new()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => config('auth.default_password'),
        ]);

        UserFactory::new()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => config('auth.default_password'),
        ]);

        UserFactory::new()->count(10)->create();
    }
}
