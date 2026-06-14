<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\Role\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

describe('RoleFilter search', function () {
    it('finds roles by name with single keyword', function () {
        Role::create(['name' => 'developer', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/roles?search=developer')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    });

    it('returns empty results for non-matching search', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/roles?search=zzzzzzz')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });
});

describe('RoleFilter sort', function () {
    it('sorts roles by name ascending', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/roles?sort=name')
            ->assertSuccessful()
            ->assertJsonPath('data.0.name', 'admin')
            ->assertJsonPath('data.2.name', 'super-admin')
            ->assertJsonPath('data.4.name', 'user');
    });

    it('sorts roles by name descending', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/roles?sort=-name')
            ->assertSuccessful()
            ->assertJsonPath('data.0.name', 'user')
            ->assertJsonPath('data.2.name', 'super-admin')
            ->assertJsonPath('data.4.name', 'admin');
    });

    it('applies default sort when no sort parameter', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/roles');

        $response->assertSuccessful();
    });
});
