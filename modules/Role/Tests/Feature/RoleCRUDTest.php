<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('admin can list roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/roles');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'Success')
        ->assertJsonStructure(['data', 'pagination']);
});

test('admin can create role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $payload = [
        'name' => 'manager',
        'permissions' => ['user.view'],
    ];

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/roles', $payload);

    $response->assertStatus(201);
    $this->assertDatabaseHas('roles', ['name' => 'manager']);
});

test('admin can update role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $role = Role::create(['name' => 'old-role']);

    $payload = [
        'name' => 'new-role-name',
        'permissions' => ['user.view', 'user.create'],
    ];

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/roles/{$role->id}", $payload);

    $response->assertStatus(200);
    $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'new-role-name']);
});

test('admin can delete role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $role = Role::create(['name' => 'to-delete']);

    $response = $this->actingAs($admin)
        ->deleteJson("/api/v1/roles/{$role->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});
