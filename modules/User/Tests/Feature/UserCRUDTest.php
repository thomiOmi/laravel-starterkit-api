<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

describe('User CRUD Operations', function () {
    it('allows admin to list users', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/users')
            ->assertSuccessful()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data',
                'meta' => ['pagination'],
            ]);
    });

    it('allows admin to create a new user', function () {
        $payload = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->actingAs($this->admin)
            ->postJson('/api/v1/users', $payload)
            ->assertCreated()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    });

    it('allows admin to update an existing user', function () {
        $user = User::factory()->create(['name' => 'Old Name']);
        $payload = [
            'name' => 'Updated Name',
            'email' => $user->email,
        ];

        $this->actingAs($this->admin)
            ->putJson("/api/v1/users/{$user->id}", $payload)
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    });

    it('allows admin to delete a user', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/users/{$user->id}")
            ->assertSuccessful();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    });

    it('denies access to unauthorized users', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/users')
            ->assertForbidden();
    });
});
