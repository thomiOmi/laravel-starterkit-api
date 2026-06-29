<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

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
            ->toBeSuccessResponse();
    });

    it('roles', function () {
        $this->adminGet('/api/v1/roles')
            ->toBeSuccessResponse();
    });

    it('permissions', function () {
        $this->adminGet('/api/v1/permissions')
            ->toBeSuccessResponse();
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

        expect(User::where('email', 'newuser@example.com')->exists())->toBeTrue();
    });

    it('a role', function () {
        $payload = [
            'name' => 'manager',
            'permissions' => ['user.view'],
        ];

        $this->adminPost('/api/v1/roles', $payload)
            ->assertStatus(Response::HTTP_CREATED);

        expect(Role::where('name', 'manager')->exists())->toBeTrue();
    });

    it('a permission', function () {
        $payload = [
            'name' => 'post.create',
            'guard_name' => 'web',
        ];

        $this->adminPost('/api/v1/permissions', $payload)
            ->assertStatus(Response::HTTP_CREATED);

        expect(Permission::where('name', 'post.create')->exists())->toBeTrue();
    });
});

describe('Update', function () {
    it('a user', function () {
        $user = User::factory()->create(['name' => 'Old Name']);
        $payload = ['name' => 'Updated Name', 'email' => $user->email];

        $this->adminPut("/api/v1/users/{$user->id}", $payload);

        expect($user->fresh()->name)->toBe('Updated Name');
    });

    it('a role', function () {
        $role = Role::create(['name' => 'old-role', 'guard_name' => 'web']);
        $payload = ['name' => 'new-role-name', 'permissions' => ['user.view', 'user.create']];

        $this->adminPut("/api/v1/roles/{$role->id}", $payload);

        expect($role->fresh()->name)->toBe('new-role-name');
    });

    it('a permission', function () {
        $permission = Permission::create(['name' => 'post.create', 'guard_name' => 'web']);
        $payload = ['name' => 'post.update'];

        $this->adminPut("/api/v1/permissions/{$permission->id}", $payload);

        expect($permission->fresh()->name)->toBe('post.update');
    });
});

describe('Delete', function () {
    it('a user', function () {
        $user = User::factory()->create();

        $this->adminDelete("/api/v1/users/{$user->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        expect($user->fresh()->deleted_at)->not->toBeNull();
    });

    it('a role', function () {
        $role = Role::create(['name' => 'to-delete', 'guard_name' => 'web']);

        $this->adminDelete("/api/v1/roles/{$role->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        expect($role->fresh()->deleted_at)->not->toBeNull();
    });

    it('a permission', function () {
        $permission = Permission::create(['name' => 'post.delete', 'guard_name' => 'web']);

        $this->adminDelete("/api/v1/permissions/{$permission->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        expect($permission->fresh()->deleted_at)->not->toBeNull();
    });
});
