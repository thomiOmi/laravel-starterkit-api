<?php

declare(strict_types=1);

namespace Modules\IAM\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\IAM\Database\Factories\UserFactory;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $rawPassword = config('auth.default_password');
        $password = Hash::make(is_string($rawPassword) ? $rawPassword : Str::random(32));

        UserFactory::new()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => $password,
        ]);

        UserFactory::new()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => $password,
        ]);

        UserFactory::new()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => $password,
        ]);

        UserFactory::new()->count(10)->create();
    }
}
