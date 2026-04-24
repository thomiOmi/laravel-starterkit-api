<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\Role\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user with user.view permission cannot list roles', function () {
    // Create user with ONLY user.view permission
    $user = User::factory()->create();
    $user->givePermissionTo('user.view');

    $response = $this->actingAs($user)
        ->getJson('/api/roles');

    // Should fail with 403 because we fixed the route to use role.view
    $response->assertStatus(403);
});

test('user with role.view permission can list roles', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('role.view');

    $response = $this->actingAs($user)
        ->getJson('/api/roles');

    $response->assertStatus(200);
});

test('unauthorized user cannot create role', function () {
    $user = User::factory()->create();
    // No permissions given

    $payload = [
        'name' => 'editor',
        'permissions' => ['user.view'],
    ];

    $response = $this->actingAs($user)
        ->postJson('/api/roles', $payload);

    $response->assertStatus(403);
});

test('user with role.create permission can create role', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('role.create');

    $payload = [
        'name' => 'editor',
        'permissions' => ['user.view'],
    ];

    $response = $this->actingAs($user)
        ->postJson('/api/roles', $payload);

    $response->assertStatus(201);
});
