<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Tests\Helpers\WithAdminUser;

uses(WithAdminUser::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
    ]);
    $this->admin->assignRole('super-admin');
});

describe('UserFilter search', function () {
    it('finds users by name with single keyword', function () {
        User::factory()->create(['name' => 'Budi Santoso']);
        User::factory()->create(['name' => 'Joko Widodo']);

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?search=Budi')
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->count('data', 1)
                ->etc()
            );
    });

    it('finds users by email with single keyword', function () {
        User::factory()->create(['email' => 'budi@test.com']);
        User::factory()->create(['email' => 'joko@test.com']);

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?search=budi@test.com')
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->count('data', 1)
                ->etc()
            );
    });

    it('requires all tokens to match with multi-keyword search', function () {
        User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@test.com']);
        User::factory()->create(['name' => 'Budi Luhur', 'email' => 'budi.luhur@test.com']);
        User::factory()->create(['name' => 'Ahmad Santoso', 'email' => 'ahmad@test.com']);

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?search=Budi%20Santoso')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Budi Santoso');
    });

    it('matches token across name and email (OR within token)', function () {
        User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@test.com']);
        User::factory()->create(['name' => 'Santoso Budi', 'email' => 'santoso@test.com']);

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?search=Budi')
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    it('returns empty results for non-matching search', function () {
        User::factory()->create(['name' => 'Budi Santoso']);

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?search=zzzzzzz')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });
});

describe('UserFilter role filter', function () {
    it('filters users by role name via filter parameter', function () {
        $adminUser = User::factory()->create(['name' => 'Admin Role']);
        $editorUser = User::factory()->create(['name' => 'Editor Role']);
        $adminUser->assignRole('super-admin');
        $editorUser->assignRole('admin');

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?filter[role]=admin')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Editor Role');
    });

    it('filters users by super-admin role', function () {
        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?filter[role]=super-admin')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'admin@example.com');
    });

    it('returns empty for non-existent role', function () {
        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?filter[role]=nonexistent')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });
});

describe('UserFilter status filter', function () {
    it('filters verified users', function () {
        User::factory()->create(['name' => 'Verified User']);

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?filter[status]=verified')
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    it('filters unverified users', function () {
        User::factory()->unverified()->create(['name' => 'Unverified User']);

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?filter[status]=unverified')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Unverified User');
    });

    it('returns all users for unknown status value', function () {
        User::factory()->count(3)->create();

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?filter[status]=unknown')
            ->assertSuccessful()
            ->assertJsonCount(4, 'data');
    });
});

describe('UserFilter sort', function () {
    it('sorts by name ascending', function () {
        User::factory()->create(['name' => 'Charlie']);
        User::factory()->create(['name' => 'Alpha']);
        User::factory()->create(['name' => 'Bravo']);

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?sort=name')
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.0.name', 'Admin User')
                ->where('data.1.name', 'Alpha')
                ->where('data.2.name', 'Bravo')
                ->where('data.3.name', 'Charlie')
                ->has('meta', fn (AssertableJson $meta) => $meta
                    ->whereType('current_page', 'integer')
                    ->whereType('last_page', 'integer')
                    ->whereType('per_page', 'integer')
                    ->whereType('total', 'integer')
                    ->etc()
                )
                ->etc()
            );
    });

    it('sorts by name descending', function () {
        User::factory()->create(['name' => 'Alpha']);
        User::factory()->create(['name' => 'Charlie']);
        User::factory()->create(['name' => 'Bravo']);

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?sort=-name')
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.0.name', 'Charlie')
                ->where('data.1.name', 'Bravo')
                ->where('data.2.name', 'Alpha')
                ->where('data.3.name', 'Admin User')
                ->has('meta', fn (AssertableJson $meta) => $meta
                    ->whereType('current_page', 'integer')
                    ->whereType('last_page', 'integer')
                    ->whereType('per_page', 'integer')
                    ->whereType('total', 'integer')
                    ->etc()
                )
                ->etc()
            );
    });

    it('sorts by multiple columns', function () {
        User::factory()->create(['name' => 'Alpha', 'email' => 'z@test.com']);
        User::factory()->create(['name' => 'Alpha', 'email' => 'a@test.com']);

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?sort=name,-email')
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.0.name', 'Admin User')
                ->where('data.1.email', 'z@test.com')
                ->where('data.2.email', 'a@test.com')
                ->has('meta', fn (AssertableJson $meta) => $meta
                    ->whereType('current_page', 'integer')
                    ->whereType('last_page', 'integer')
                    ->whereType('per_page', 'integer')
                    ->whereType('total', 'integer')
                    ->etc()
                )
                ->etc()
            );
    });
});

describe('UserFilter combinations', function () {
    it('combines search with role filter', function () {
        $user = User::factory()->create(['name' => 'Target User']);
        $user->assignRole('admin');
        User::factory()->create(['name' => 'Target User'])->assignRole('super-admin');

        Sanctum::actingAs($this->admin);

        $this
            ->getJson('/api/v1/users?search=Target&filter[role]=admin')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', $user->email);
    });

    it('combines search with sort', function () {
        User::factory()->create(['name' => 'Alpha User']);
        User::factory()->create(['name' => 'Beta User']);

        Sanctum::actingAs($this->admin);

        $response = $this
            ->getJson('/api/v1/users?search=User&sort=-name')
            ->assertSuccessful();

        $names = collect($response->json('data'))->pluck('name');
        expect($names[0])->toBe('Beta User');
        expect($names[1])->toBe('Alpha User');
    });
});
