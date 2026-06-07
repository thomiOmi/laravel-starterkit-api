<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

describe('User Filter and Sort V1', function () {
    it('allows sorting by allowed columns', function () {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Zebra']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/V1/users?sort_by=name&sort_direction=asc')
            ->assertSuccessful();

        $data = $response->json('data');
        expect($data[0]['name'])->toBe('Alice');

        $response = $this->actingAs($this->admin)
            ->getJson('/api/V1/users?sort_by=name&sort_direction=desc')
            ->assertSuccessful();

        $data = $response->json('data');
        expect($data[0]['name'])->toBe('Zebra');
    });

    it('defaults to latest when sorting by disallowed column', function () {
        // Create an old user
        User::factory()->create(['created_at' => now()->subDays(2)]);
        // Create a new user that is definitely the latest
        $newUser = User::factory()->create(['created_at' => now()->addDay()]);

        // 'password' is not in allowedSorts
        $response = $this->actingAs($this->admin)
            ->getJson('/api/V1/users?sort_by=password&sort_direction=asc')
            ->assertSuccessful();

        $data = $response->json('data');
        // Should default to latest(), so the newUser should be first
        expect($data[0]['id'])->toBe($newUser->id);
    });

    it('filters by role using whereRelation', function () {
        $userWithRole = User::factory()->create();
        $userWithRole->assignRole('user');

        $response = $this->actingAs($this->admin)
            ->getJson('/api/V1/users?role=user')
            ->assertSuccessful();

        $data = $response->json('data');
        expect(count($data))->toBe(1);
        expect($data[0]['id'])->toBe($userWithRole->id);
    });

    it('filters by created_at using direct comparison', function () {
        $pastUser = User::factory()->create(['created_at' => '2020-01-01 00:00:00']);
        $todayUser = User::factory()->create(['created_at' => now()->format('Y-m-d H:i:s')]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/V1/users?created_at[from]=2024-01-01')
            ->assertSuccessful();

        $data = $response->json('data');
        // Should only include admin and todayUser, not pastUser
        $ids = collect($data)->pluck('id');
        expect($ids)->toContain($todayUser->id);
        expect($ids)->not->toContain($pastUser->id);
    });
});
