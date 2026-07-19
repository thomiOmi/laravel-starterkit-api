<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['web', 'sanctum'] as $guard) {
        Role::create(['name' => RoleEnum::Admin->value, 'guard_name' => $guard]);
        Role::create(['name' => RoleEnum::User->value, 'guard_name' => $guard]);
    }

    $admin = loginAsUser();
    $perm = Permission::firstOrCreate(['name' => PermissionEnum::UserView->value, 'guard_name' => 'sanctum']);
    $admin->givePermissionTo($perm);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin->update(['name' => 'Admin User']);
});

describe('Filtering', function () {
    it('filters by name using LIKE partial match', function () {
        User::factory()->create(['name' => 'Alice Wonderland']);
        User::factory()->create(['name' => 'Bob Builder']);

        $response = $this->getJson('/api/v1/users?filter[name]=Alice');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Alice Wonderland');
    })->group('v1', 'filter');

    it('filters by status using exact match via $exactMatchColumns', function () {
        User::factory()->count(2)->create(['status' => UserStatus::Active]);

        $response = $this->getJson('/api/v1/users?filter[status]=active');

        expect($response->json('data'))->toHaveCount(3);
    })->group('v1', 'filter');

    it('filters by role using strategy method delegating to Spatie scope', function () {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::User->value);

        $response = $this->getJson('/api/v1/users?filter[role]=user');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.id'))->toBe($user->id);
    })->group('v1', 'filter');

    it('filters array value as WHERE IN', function () {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);
        User::factory()->create(['name' => 'Charlie']);

        $response = $this->getJson('/api/v1/users?filter[name][]=Alice&filter[name][]=Bob');

        expect($response->json('data'))->toHaveCount(2);
    })->group('v1', 'filter');

    it('filters with neq: operator prefix', function () {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);

        $response = $this->getJson('/api/v1/users?filter[name]=neq:Bob');

        $names = collect($response->json('data'))->pluck('name')->all();
        expect($names)->toHaveCount(2)
            ->toContain('Alice')
            ->toContain('Admin User')
            ->not->toContain('Bob');
    })->group('v1', 'filter');

    it('filters with like: operator prefix', function () {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Alex']);
        User::factory()->create(['name' => 'Bob']);

        $response = $this->getJson('/api/v1/users?filter[name]=like:Al%');

        expect($response->json('data'))->toHaveCount(2);
    })->group('v1', 'filter');

    it('filters with gt: operator prefix on created_at', function () {
        User::factory()->create(['name' => 'Old', 'created_at' => now()->subDays(5)]);
        User::factory()->create(['name' => 'New', 'created_at' => now()->subHour()]);

        $response = $this->getJson('/api/v1/users?filter[created_at]=gt:'.now()->subDays(1)->format('Y-m-d H:i:s'));

        $names = collect($response->json('data'))->pluck('name')->all();
        expect($names)->toHaveCount(2)
            ->toContain('New')
            ->toContain('Admin User')
            ->not->toContain('Old');
    })->group('v1', 'filter');

    it('filters with gte: operator prefix on created_at', function () {
        $target = now()->subDays(2)->format('Y-m-d H:i:s');
        User::factory()->create(['name' => 'Old', 'created_at' => now()->subDays(5)]);
        User::factory()->create(['name' => 'Exact', 'created_at' => now()->subDays(2)]);
        User::factory()->create(['name' => 'New', 'created_at' => now()->subHour()]);

        $response = $this->getJson("/api/v1/users?filter[created_at]=gte:{$target}");

        $names = collect($response->json('data'))->pluck('name')->all();
        expect($names)->toHaveCount(3)
            ->toContain('Exact')
            ->toContain('New')
            ->toContain('Admin User')
            ->not->toContain('Old');
    })->group('v1', 'filter');

    it('filters with lt: operator prefix on created_at', function () {
        User::factory()->create(['name' => 'Old', 'created_at' => now()->subDays(5)]);
        User::factory()->create(['name' => 'New', 'created_at' => now()->subHour()]);

        $response = $this->getJson('/api/v1/users?filter[created_at]=lt:'.now()->subDays(1)->format('Y-m-d H:i:s'));

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Old');
    })->group('v1', 'filter');

    it('filters with lte: operator prefix on created_at', function () {
        $target = now()->subDays(2)->format('Y-m-d H:i:s');
        User::factory()->create(['name' => 'Old', 'created_at' => now()->subDays(5)]);
        User::factory()->create(['name' => 'Exact', 'created_at' => now()->subDays(2)]);
        User::factory()->create(['name' => 'New', 'created_at' => now()->subHour()]);

        $response = $this->getJson("/api/v1/users?filter[created_at]=lte:{$target}");

        $names = collect($response->json('data'))->pluck('name')->all();
        expect($names)->toHaveCount(2)
            ->toContain('Old')
            ->toContain('Exact')
            ->not->toContain('New')
            ->not->toContain('Admin User');
    })->group('v1', 'filter');

    it('combines multiple filters with AND logic', function () {
        User::factory()->create(['name' => 'Alice', 'status' => UserStatus::Active]);
        User::factory()->create(['name' => 'Alice', 'status' => UserStatus::Inactive]);
        User::factory()->create(['name' => 'Bob', 'status' => UserStatus::Active]);

        $response = $this->getJson('/api/v1/users?filter[name]=Alice&filter[status]=active');

        expect($response->json('data'))->toHaveCount(1);
    })->group('v1', 'filter');
});

describe('Search', function () {
    it('searches across searchable columns', function () {
        User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);

        $response = $this->getJson('/api/v1/users?search=alice');

        expect($response->json('data'))->toHaveCount(1);
    })->group('v1', 'filter');
});

describe('Sorting', function () {
    it('sorts ascending by default', function () {
        User::factory()->create(['name' => 'Aaron']);
        User::factory()->create(['name' => 'Zara']);

        $response = $this->getJson('/api/v1/users?sort=name');

        $names = collect($response->json('data'))->pluck('name')->all();
        expect($names[0])->toBe('Aaron')
            ->and($names[1])->toBe('Admin User')
            ->and($names[2])->toBe('Zara');
    })->group('v1', 'filter');

    it('sorts descending with minus prefix', function () {
        User::factory()->create(['name' => 'Aaron']);
        User::factory()->create(['name' => 'Zara']);

        $response = $this->getJson('/api/v1/users?sort=-name');

        $names = collect($response->json('data'))->pluck('name')->all();
        expect($names[0])->toBe('Zara')
            ->and($names[1])->toBe('Admin User')
            ->and($names[2])->toBe('Aaron');
    })->group('v1', 'filter');
});

describe('Pagination', function () {
    it('paginates results with default per page', function () {
        User::factory()->count(20)->create();

        $response = $this->getJson('/api/v1/users');

        expect($response->json('meta.per_page'))->toBe(15);
    })->group('v1', 'filter');

    it('respects requested page size', function () {
        User::factory()->count(20)->create();

        $response = $this->getJson('/api/v1/users?page[size]=5');

        expect($response->json('data'))->toHaveCount(5)
            ->and($response->json('meta.per_page'))->toBe(5);
    })->group('v1', 'filter');

    it('sets page size to 100 as maximum', function () {
        User::factory()->count(150)->create();

        $response = $this->getJson('/api/v1/users?page[size]=100');

        expect($response->json('meta.per_page'))->toBe(100);
    })->group('v1', 'filter');

    it('rejects page size exceeding 100', function () {
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/users?page[size]=200');

        $response->assertStatus(422);
    })->group('v1', 'filter');

    it('returns second page of results', function () {
        User::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/users?page[size]=10&page[number]=2');

        expect($response->json('data'))->toHaveCount(10)
            ->and($response->json('meta.current_page'))->toBe(2);
    })->group('v1', 'filter');

    it('defaults to page 1 when page[number] is missing', function () {
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/users?page[size]=5');

        expect($response->json('meta.current_page'))->toBe(1);
    })->group('v1', 'filter');

    it('rejects page[number] of 0', function () {
        $response = $this->getJson('/api/v1/users?page[size]=5&page[number]=0');

        $response->assertStatus(422);
    })->group('v1', 'filter');

    it('rejects non-integer page[size]', function () {
        $response = $this->getJson('/api/v1/users?page[size]=abc');

        $response->assertStatus(422);
    })->group('v1', 'filter');

    it('rejects negative page[size]', function () {
        $response = $this->getJson('/api/v1/users?page[size]=-1');

        $response->assertStatus(422);
    })->group('v1', 'filter');
});

describe('Sparse Fields', function () {
    it('selects only requested fields', function () {
        $response = $this->getJson('/api/v1/users?fields[users]=id,name');

        expect($response->json('data.0'))->toHaveKeys(['id', 'name']);
    })->group('v1', 'filter');

    it('rejects unknown field names in non-production', function () {
        $response = $this->getJson('/api/v1/users?fields[users]=id,name,unknown_field');

        $response->assertStatus(400);
    })->group('v1', 'filter');
});

describe('Includes', function () {
    it('eager loads included roles relation', function () {
        $admin = User::firstWhere('name', 'Admin User');
        $admin->assignRole(RoleEnum::Admin->value);

        $response = $this->getJson('/api/v1/users?include=roles');

        expect($response->json('data.0.roles'))->toBeArray()
            ->and($response->json('data.0.roles.0'))->toBe(RoleEnum::Admin->value);
    })->group('v1', 'filter');
});

describe('Trashed Filter', function () {
    it('includes soft-deleted records with trashed=with', function () {
        User::factory()->count(3)->create();
        $deleted = User::factory()->create();
        $deleted->delete();

        $response = $this->getJson('/api/v1/users?filter[trashed]=with');

        expect($response->json('data'))->toHaveCount(5);
    })->group('v1', 'filter');

    it('shows only soft-deleted records with trashed=only', function () {
        User::factory()->count(3)->create();
        $deleted = User::factory()->create();
        $deleted->delete();

        $response = $this->getJson('/api/v1/users?filter[trashed]=only');

        expect($response->json('data'))->toHaveCount(1);
    })->group('v1', 'filter');
});
