<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\Role\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('admin can bulk delete users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $users = User::factory()->count(5)->create();
    /** @var array<int, string> $ids */
    $ids = $users->pluck('id')->map(fn (mixed $id) => is_scalar($id) ? (string) $id : '')->toArray();

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/users/bulk/delete', [
            'ids' => $ids,
            'action' => 'delete',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.count', 5);

    foreach ($ids as $id) {
        $this->assertSoftDeleted('users', ['id' => $id]);
    }
});

test('admin can bulk delete roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $roles = [];
    $roles[] = Role::create(['name' => 'role1']);
    $roles[] = Role::create(['name' => 'role2']);
    /** @var array<int, string> $ids */
    $ids = collect($roles)->pluck('id')->map(fn (mixed $id) => is_scalar($id) ? (string) $id : '')->toArray();

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/roles/bulk/delete', [
            'ids' => $ids,
            'action' => 'delete',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.count', 2);
});

test('bulk update action is rejected for security', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $roles = [];
    $roles[] = Role::create(['name' => 'role1']);
    $roles[] = Role::create(['name' => 'role2']);
    /** @var array<int, string> $ids */
    $ids = collect($roles)->pluck('id')->map(fn (mixed $id) => is_scalar($id) ? (string) $id : '')->toArray();

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/roles/bulk/delete', [
            'ids' => $ids,
            'action' => 'update',
            'data' => ['description' => 'Bulk updated description'],
        ]);

    $response->assertStatus(403);
});
