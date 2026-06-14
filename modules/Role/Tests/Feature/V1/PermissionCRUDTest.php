<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\Role\Models\Permission;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

describe('Permission CRUD Operations V1', function () {
    it('allows admin to list permissions', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/permissions')
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
                'message',
            ]);
    });

    it('allows admin to create a new permission', function () {
        $payload = [
            'name' => 'post.create',
            'guard_name' => 'web',
        ];

        $this->actingAs($this->admin)
            ->postJson('/api/v1/permissions', $payload)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('data.name', 'post.create');

        $this->assertDatabaseHas('permissions', ['name' => 'post.create']);
    });

    it('allows admin to view a permission', function () {
        $permission = Permission::create(['name' => 'post.create', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->getJson("/api/v1/permissions/{$permission->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'post.create')
            ->assertJsonPath('data.guard_name', 'web');
    });

    it('allows admin to update a permission', function () {
        $permission = Permission::create(['name' => 'post.create', 'guard_name' => 'web']);
        $payload = [
            'name' => 'post.update',
        ];

        $this->actingAs($this->admin)
            ->putJson("/api/v1/permissions/{$permission->id}", $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'post.update');

        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'post.update']);
    });

    it('allows admin to delete a permission', function () {
        $permission = Permission::create(['name' => 'post.delete', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/permissions/{$permission->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('permissions', ['id' => $permission->id]);
    });

    it('denies access to unauthorized users', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/permissions')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('validates unique permission name', function () {
        Permission::create(['name' => 'post.create', 'guard_name' => 'web']);
        $payload = [
            'name' => 'post.create',
        ];

        $this->actingAs($this->admin)
            ->postJson('/api/v1/permissions', $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('filters permissions by guard name', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/permissions?filter[guard]=web')
            ->assertSuccessful()
            ->assertJsonCount(12, 'data');
    });

    it('searches permissions by keyword', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/permissions?search=user.view')
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    it('sorts permissions by name', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/permissions?sort=name')
            ->assertSuccessful();
    });
});
