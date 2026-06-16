<?php

declare(strict_types=1);

use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helpers\WithAdminUser;

uses(WithAdminUser::class);

beforeEach(function () {
    $this->setUpAdminUser();
});

describe('Role CRUD Operations V1', function () {
    it('denies access to unauthorized users', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/roles')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('creates a new role', function () {
        $payload = [
            'name' => 'editor',
            'description' => 'Can edit content',
        ];

        $this->adminPost('/api/v1/roles', $payload)
            ->assertCreated()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'name', 'description', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.name', 'editor')
            ->assertJsonPath('data.description', 'Can edit content');
    });

    it('creates a role with permissions', function () {
        $payload = [
            'name' => 'moderator',
            'permissions' => ['user.view', 'user.create'],
        ];

        $this->adminPost('/api/v1/roles', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'moderator');
    });

    it('shows a role', function () {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $this->adminGet("/api/v1/roles/{$role->id}")
            ->assertJsonPath('data.id', $role->id)
            ->assertJsonPath('data.name', 'editor');
    });

    it('returns 404 for non-existent role', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/roles/non-existent-id')
            ->assertNotFound();
    });

    it('updates a role', function () {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $payload = [
            'name' => 'editor-updated',
            'description' => 'Updated description',
        ];

        $this->adminPut("/api/v1/roles/{$role->id}", $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'editor-updated')
            ->assertJsonPath('data.description', 'Updated description');
    });

    it('updates a role with permissions', function () {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $payload = [
            'name' => 'editor',
            'permissions' => ['user.view', 'role.view'],
        ];

        $this->adminPut("/api/v1/roles/{$role->id}", $payload)
            ->assertSuccessful();
    });

    it('deletes a role', function () {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $this->adminDelete("/api/v1/roles/{$role->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted($role);
    });

    it('prevents deleting super-admin role', function () {
        $superAdmin = Role::where('name', 'super-admin')->first();

        $this->adminDelete('/api/v1/roles/'.$superAdmin->id)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('validates unique role name', function () {
        Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $payload = ['name' => 'editor'];

        $this->actingAs($this->admin)
            ->postJson('/api/v1/roles', $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });
});
