<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Testing\TestResponse;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

trait WithAdminUser
{
    public User $admin;

    protected function setUpAdminUser(): void
    {
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    protected function adminGet(string $uri): TestResponse
    {
        return $this->actingAs($this->admin)->getJson($uri)->assertSuccessful();
    }

    protected function adminPost(string $uri, array $data = []): TestResponse
    {
        return $this->actingAs($this->admin)->postJson($uri, $data);
    }

    protected function adminPut(string $uri, array $data = []): TestResponse
    {
        return $this->actingAs($this->admin)->putJson($uri, $data);
    }

    protected function adminDelete(string $uri): TestResponse
    {
        return $this->actingAs($this->admin)->deleteJson($uri);
    }
}
