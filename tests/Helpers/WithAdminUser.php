<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

trait WithAdminUser
{
    public User $admin;

    protected function setUpAdminUser(): void
    {
        // Use artisan instead of $this->seed if it fails in some contexts
        Artisan::call('db:seed', ['--class' => RoleSeeder::class]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    protected function adminGet(string $uri): TestResponse
    {
        Sanctum::actingAs($this->admin);

        return $this->getJson($uri);
    }

    protected function adminPost(string $uri, array $data = []): TestResponse
    {
        Sanctum::actingAs($this->admin);

        return $this->postJson($uri, $data);
    }

    protected function adminPut(string $uri, array $data = []): TestResponse
    {
        Sanctum::actingAs($this->admin);

        return $this->putJson($uri, $data);
    }

    protected function adminDelete(string $uri): TestResponse
    {
        Sanctum::actingAs($this->admin);

        return $this->deleteJson($uri);
    }
}
