<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user has avatar field', function () {
    $user = User::factory()->create(['avatar' => 'my-avatar.png']);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $response = $this->actingAs($admin)
        ->getJson("/api/v1/users/{$user->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.avatar', 'my-avatar.png');
});

test('role has description field', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $payload = [
        'name' => 'manager',
        'description' => 'Manager of the system',
        'permissions' => ['user.view'],
    ];

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/roles', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.description', 'Manager of the system');

    $this->assertDatabaseHas('roles', [
        'name' => 'manager',
        'description' => 'Manager of the system',
    ]);
});
