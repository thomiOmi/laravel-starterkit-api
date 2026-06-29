<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Modules\Role\Models\Permission;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helpers\WithAdminUser;

uses(WithAdminUser::class);

beforeEach(function () {
    $this->setUpAdminUser();
});

describe('Permission CRUD Operations V1', function () {
    it('lists paginated permissions', function () {
        $this->adminGet('/api/v1/permissions')
            ->assertJson(fn (AssertableJson $json) => $json->has('data')
                ->has('meta', fn (AssertableJson $meta) => $meta->whereType('current_page', 'integer')
                    ->whereType('last_page', 'integer')
                    ->whereType('per_page', 'integer')
                    ->where('total', fn (int $total) => $total >= 12)
                    ->etc()
                )
                ->etc()
            );
    });

    it('paginates permissions with custom page size', function () {
        $this->adminGet('/api/v1/permissions?page[size]=5')
            ->assertJsonPath('meta.per_page', 5);
    });

    it('allows admin to view a permission', function () {
        $permission = Permission::create(['name' => 'post.create', 'guard_name' => 'web']);

        $this->adminGet("/api/v1/permissions/{$permission->id}")
            ->assertJsonPath('data.name', 'post.create')
            ->assertJsonPath('data.guard_name', 'web');
    });

    it('denies access to unauthorized users', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/permissions')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('validates unique permission name', function () {
        Permission::create(['name' => 'post.create', 'guard_name' => 'web']);

        $this->actingAs($this->admin);

        $this->postJson('/api/v1/permissions', ['name' => 'post.create'])
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

    it('updates a permission name and guard', function () {
        $permission = Permission::create(['name' => 'temp.update', 'guard_name' => 'web']);

        $this->actingAs($this->admin);

        $this->putJson("/api/v1/permissions/{$permission->id}", [
            'name' => 'temp.updated',
        ])
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'temp.updated');
    });

    it('validates unique permission name on update', function () {
        Permission::create(['name' => 'temp.existing', 'guard_name' => 'web']);
        $target = Permission::create(['name' => 'temp.target', 'guard_name' => 'web']);

        $this->actingAs($this->admin);

        $this->putJson("/api/v1/permissions/{$target->id}", [
            'name' => 'temp.existing',
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('returns 404 when updating non-existent permission', function () {
        $this->actingAs($this->admin);

        $this->putJson('/api/v1/permissions/non-existent-id', [
            'name' => 'does.not.matter',
        ])
            ->assertNotFound();
    });

    it('allows admin to delete a permission', function () {
        $permission = Permission::create(['name' => 'temp.delete', 'guard_name' => 'web']);

        $this->actingAs($this->admin);

        $this->deleteJson("/api/v1/permissions/{$permission->id}")
            ->assertNoContent();

        $this->assertSoftDeleted($permission);
    });

    it('returns 403 when deleting non-existent permission', function () {
        $this->actingAs($this->admin);

        $this->deleteJson('/api/v1/permissions/non-existent-id')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('denies unauthorized user from deleting permission', function () {
        $permission = Permission::create(['name' => 'temp.no.delete', 'guard_name' => 'web']);
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/permissions/{$permission->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });
});
