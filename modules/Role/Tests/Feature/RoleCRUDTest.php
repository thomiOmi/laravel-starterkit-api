<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\Role\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

describe('Role CRUD Operations', function () {
    it('allows admin to list roles', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/roles')
            ->assertSuccessful()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data', 'meta' => ['pagination']]);
    });

    it('allows admin to create a new role', function () {
        $payload = [
            'name' => 'manager',
            'permissions' => ['user.view'],
        ];

        $this->actingAs($this->admin)
            ->postJson('/api/v1/roles', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('roles', ['name' => 'manager']);
    });

    it('allows admin to update an existing role', function () {
        $role = Role::create(['name' => 'old-role']);
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
        $role = Role::create(['name' => 'to-delete']);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/roles/{$role->id}")
            ->assertSuccessful();

        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    });
});
