<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('authenticated user with view permission can list users', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $response = $this->actingAs($user)
        ->getJson('/api/v1/users');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'data',
            'meta' => ['pagination'],
        ]);
});

test('admin can create new user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $payload = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/users', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

test('admin can update user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $user = User::factory()->create(['name' => 'Old Name']);

    $payload = [
        'name' => 'Updated Name',
        'email' => $user->email,
    ];

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/users/{$user->id}", $payload);

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
});

test('admin can delete user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $user = User::factory()->create();

    $response = $this->actingAs($admin)
        ->deleteJson("/api/v1/users/{$user->id}");

    $response->assertStatus(200);
    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

test('unauthorized user cannot list users', function () {
    $user = User::factory()->create();
    // No role assigned

    $response = $this->actingAs($user)
        ->getJson('/api/v1/users');

    $response->assertStatus(403);
});
