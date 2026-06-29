<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helpers\WithAdminUser;

uses(WithAdminUser::class);

beforeEach(function () {
    $this->setUpAdminUser();
});

describe('Role CRUD Operations V1', function () {
    it('lists paginated roles', function () {
        $this->adminGet('/api/v1/roles')
            ->assertJson(fn (AssertableJson $json) => $json->has('data')
                ->has('meta', fn (AssertableJson $meta) => $meta->whereType('current_page', 'integer')
                    ->whereType('last_page', 'integer')
                    ->whereType('per_page', 'integer')
                    ->where('total', fn (int $total) => $total >= 5)
                    ->etc()
                )
                ->etc()
            );
    });

    it('paginates roles with custom page size', function () {
        $this->adminGet('/api/v1/roles?page[size]=2')
            ->assertJsonPath('meta.per_page', 2);
    });

    it('denies access to unauthorized users', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/roles')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('creates a new role', function () {
        $payload = [
            'name' => 'editor',
            'description' => 'Can edit content',
        ];

        $this->adminPost('/api/v1/roles', $payload)
            ->assertCreated()
            ->assertJson(fn (AssertableJson $json) => $json->whereType('status', 'integer')
                ->where('data.name', 'editor')
                ->where('data.description', 'Can edit content')
                ->whereType('data.id', 'string')
                ->whereType('data.created_at', 'string')
                ->whereType('data.updated_at', 'string')
                ->etc()
            );
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
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/roles/non-existent-id')
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

        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/roles', ['name' => 'editor'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });
});
