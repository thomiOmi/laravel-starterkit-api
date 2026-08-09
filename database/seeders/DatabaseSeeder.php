<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\Media\Database\Seeders\MediaSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(IAMSeeder::class);
        $this->call(MediaSeeder::class);
    }
}
