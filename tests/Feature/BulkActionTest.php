<?php

declare(strict_types=1);

use Laravel\Sanctum\Sanctum;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\Role\Models\Role;
use Modules\User\Models\User;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('admin can bulk delete users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    Sanctum::actingAs($admin);

    $users = User::factory()->count(5)->create();
    /** @var array<int, string> $ids */
    $ids = $users->pluck('id')->map(fn (mixed $id) => is_scalar($id) ? (string) $id : '')->toArray();

    $this->postJson('/api/v1/users/bulk/delete', [
        'ids' => $ids,
        'action' => 'delete',
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.count', 5);

    foreach ($ids as $id) {
        $this->assertSoftDeleted('users', ['id' => $id]);
    }
});

test('admin can bulk delete roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    Sanctum::actingAs($admin);

    $roles = [];
    $roles[] = Role::create(['name' => 'role1']);
    $roles[] = Role::create(['name' => 'role2']);
    /** @var array<int, string> $ids */
    $ids = collect($roles)->pluck('id')->map(fn (mixed $id) => is_scalar($id) ? (string) $id : '')->toArray();

    $this->postJson('/api/v1/roles/bulk/delete', [
        'ids' => $ids,
        'action' => 'delete',
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.count', 2);
});

test('admin cannot bulk delete super-admin role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    Sanctum::actingAs($admin);

    $superAdminRole = Role::where('name', 'super-admin')->firstOrFail();
    $otherRole = Role::create(['name' => 'other-role']);

    $ids = [(string) $superAdminRole->id, (string) $otherRole->id];

    $this->postJson('/api/v1/roles/bulk/delete', [
        'ids' => $ids,
        'action' => 'delete',
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.count', 1);

    $this->assertDatabaseHas('roles', ['name' => 'super-admin', 'deleted_at' => null]);
    $this->assertSoftDeleted('roles', ['name' => 'other-role']);
});

test('admin can bulk restore users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    Sanctum::actingAs($admin);

    $users = User::factory()->count(3)->create();
    $ids = $users->pluck('id')->map(fn (mixed $id) => (string) $id)->toArray();
    User::whereIn('id', $ids)->delete();

    $this->postJson('/api/v1/users/bulk/restore', [
        'ids' => $ids,
        'action' => 'restore',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.count', 3);

    foreach ($ids as $id) {
        $this->assertNotSoftDeleted('users', ['id' => $id]);
    }
});

test('admin can bulk restore roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    Sanctum::actingAs($admin);

    $role = Role::create(['name' => 'temp-role']);
    $ids = [(string) $role->id];
    $role->delete();

    $this->postJson('/api/v1/roles/bulk/restore', [
        'ids' => $ids,
        'action' => 'restore',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.count', 1);

    $this->assertNotSoftDeleted('roles', ['name' => 'temp-role']);
});

test('bulk restore requires valid action parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/roles/bulk/restore', [
        'ids' => ['invalid-id'],
        'action' => 'delete',
    ])
        ->assertStatus(403);
});

test('bulk restore requires authentication', function () {
    $response = $this->postJson('/api/v1/users/bulk/restore', [
        'ids' => ['some-id'],
        'action' => 'restore',
    ]);

    $response->assertStatus(401);
});

test('bulk update action is rejected for security', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    Sanctum::actingAs($admin);

    $roles = [];
    $roles[] = Role::create(['name' => 'role1']);
    $roles[] = Role::create(['name' => 'role2']);
    /** @var array<int, string> $ids */
    $ids = collect($roles)->pluck('id')->map(fn (mixed $id) => is_scalar($id) ? (string) $id : '')->toArray();

    $this->postJson('/api/v1/roles/bulk/delete', [
        'ids' => $ids,
        'action' => 'update',
        'data' => ['description' => 'Bulk updated description'],
    ])
        ->assertStatus(403);
});
