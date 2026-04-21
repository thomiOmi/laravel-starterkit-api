<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Role\Database\Seeders\RoleSeeder as RoleModuleSeeder;
use Modules\User\Database\Seeders\UserModuleSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserModuleSeeder::class,
            RoleModuleSeeder::class,
        ]);
    }
}
