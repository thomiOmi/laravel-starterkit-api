<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

describe('Role CRUD Operations V1', function () {
    it('allows admin to list roles', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/roles')
            ->assertSuccessful()
            ->assertJsonStructure(['data', 'message']);
    });

    it('allows admin to create a new role', function () {
        $payload = [
            'name' => 'manager',
            'permissions' => ['user.view'],
        ];

        $this->actingAs($this->admin)
            ->postJson('/api/v1/roles', $payload)
            ->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('roles', ['name' => 'manager']);
    });

    it('allows admin to update an existing role', function () {
        $role = Role::create(['name' => 'old-role', 'guard_name' => 'web']);
        $payload = [
            'name' => 'new-role-name',
            'permissions' => ['user.view', 'user.create'],
        ];

        $this->actingAs($this->admin)
            ->putJson("/api/v1/roles/{$role->id}", $payload)
            ->assertSuccessful();

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'new-role-name']);
    });

    it('allows admin to delete a role', function () {
        $role = Role::create(['name' => 'to-delete', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/roles/{$role->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    });
});
