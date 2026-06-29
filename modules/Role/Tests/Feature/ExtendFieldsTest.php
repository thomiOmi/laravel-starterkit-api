<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('role has description field', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/roles', [
        'name' => 'manager',
        'description' => 'Manager of the system',
        'permissions' => ['user.view'],
    ])
        ->assertStatus(201)
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('data.description', 'Manager of the system')
            ->whereType('data.id', 'string')
            ->whereType('data.name', 'string')
            ->etc()
        );

    $this->assertDatabaseHas('roles', [
        'name' => 'manager',
        'description' => 'Manager of the system',
    ]);
});
