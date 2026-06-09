<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

describe('User CRUD Operations V1', function () {
    it('allows admin to list users', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/V1/users')
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
                'message',
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
            ->postJson('/api/V1/users', $payload)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('data.email', 'newuser@example.com');

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    });

    it('allows admin to update an existing user', function () {
        $user = User::factory()->create(['name' => 'Old Name']);
        $payload = [
            'name' => 'Updated Name',
            'email' => $user->email,
        ];

        $this->actingAs($this->admin)
            ->putJson("/api/V1/users/{$user->id}", $payload)
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    });

    it('allows admin to delete a user', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/V1/users/{$user->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    });

    it('denies access to unauthorized users', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/V1/users')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });
});
