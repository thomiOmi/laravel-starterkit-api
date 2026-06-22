<?php

declare(strict_types=1);

use Modules\Role\Models\Permission;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helpers\WithAdminUser;

uses(WithAdminUser::class);

beforeEach(function () {
    $this->setUpAdminUser();
});

describe('List', function () {
    it('users', function () {
        $this->adminGet('/api/v1/users')
            ->assertJsonStructure(['status', 'title', 'detail', 'data']);
    });

    it('roles', function () {
        $this->adminGet('/api/v1/roles')
            ->assertJsonStructure(['status', 'title', 'detail', 'data']);
    });

    it('permissions', function () {
        $this->adminGet('/api/v1/permissions')
            ->assertJsonStructure(['status', 'title', 'detail', 'data']);
    });
});

describe('Create', function () {
    it('a user', function () {
        $password = config('auth.default_password');
        $payload = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $this->adminPost('/api/v1/users', $payload)
            ->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    });

    it('a role', function () {
        $payload = [
            'name' => 'manager',
            'permissions' => ['user.view'],
        ];

        $this->adminPost('/api/v1/roles', $payload)
            ->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('roles', ['name' => 'manager']);
    });

    it('a permission', function () {
        $payload = [
            'name' => 'post.create',
            'guard_name' => 'web',
        ];

        $this->adminPost('/api/v1/permissions', $payload)
            ->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('permissions', ['name' => 'post.create']);
    });
});

describe('Update', function () {
    it('a user', function () {
        $user = User::factory()->create(['name' => 'Old Name']);
        $payload = ['name' => 'Updated Name', 'email' => $user->email];

        $this->adminPut("/api/v1/users/{$user->id}", $payload);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    });

    it('a role', function () {
        $role = Role::create(['name' => 'old-role', 'guard_name' => 'web']);
        $payload = ['name' => 'new-role-name', 'permissions' => ['user.view', 'user.create']];

        $this->adminPut("/api/v1/roles/{$role->id}", $payload);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'new-role-name']);
    });

    it('a permission', function () {
        $permission = Permission::create(['name' => 'post.create', 'guard_name' => 'web']);
        $payload = ['name' => 'post.update'];

        $this->adminPut("/api/v1/permissions/{$permission->id}", $payload);

        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'post.update']);
    });
});

describe('Delete', function () {
    it('a user', function () {
        $user = User::factory()->create();

        $this->adminDelete("/api/v1/users/{$user->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    });

    it('a role', function () {
        $role = Role::create(['name' => 'to-delete', 'guard_name' => 'web']);

        $this->adminDelete("/api/v1/roles/{$role->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    });

    it('a permission', function () {
        $permission = Permission::create(['name' => 'post.delete', 'guard_name' => 'web']);

        $this->adminDelete("/api/v1/permissions/{$permission->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('permissions', ['id' => $permission->id]);
    });
});
