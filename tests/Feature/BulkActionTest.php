<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('admin can bulk delete users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $users = User::factory()->count(5)->create();
    $ids = $users->pluck('id')->map(fn ($id) => (string) $id)->toArray();

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/users/bulk', [
            'ids' => $ids,
            'action' => 'delete',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.count', 5);

    foreach ($ids as $id) {
        $this->assertSoftDeleted('users', ['id' => $id]);
    }
});

test('admin can bulk update roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $roles = [];
    $roles[] = Role::create(['name' => 'role1']);
    $roles[] = Role::create(['name' => 'role2']);
    $ids = collect($roles)->pluck('id')->map(fn ($id) => (string) $id)->toArray();

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/roles/bulk', [
            'ids' => $ids,
            'action' => 'update',
            'data' => ['description' => 'Bulk updated description'],
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.count', 2);

    $this->assertDatabaseHas('roles', ['name' => 'role1', 'description' => 'Bulk updated description']);
    $this->assertDatabaseHas('roles', ['name' => 'role2', 'description' => 'Bulk updated description']);
});
