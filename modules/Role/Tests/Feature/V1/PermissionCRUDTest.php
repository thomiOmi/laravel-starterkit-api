<?php

declare(strict_types=1);

use Modules\Role\Models\Permission;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helpers\WithAdminUser;

uses(WithAdminUser::class);

beforeEach(function () {
    $this->setUpAdminUser();
});

describe('Permission CRUD Operations V1', function () {
    it('allows admin to view a permission', function () {
        $permission = Permission::create(['name' => 'post.create', 'guard_name' => 'web']);

        $this->adminGet("/api/v1/permissions/{$permission->id}")
            ->assertJsonPath('data.name', 'post.create')
            ->assertJsonPath('data.guard_name', 'web');
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
        $this->adminGet('/api/v1/permissions?filter[guard]=web')
            ->assertJsonCount(12, 'data');
    });

    it('searches permissions by keyword', function () {
        $this->adminGet('/api/v1/permissions?search=user.view')
            ->assertJsonCount(2, 'data');
    });

    it('sorts permissions by name', function () {
        $this->adminGet('/api/v1/permissions?sort=name');
    });
});
