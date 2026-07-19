<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['web', 'sanctum'] as $guard) {
        Role::create(['name' => RoleEnum::Admin->value, 'guard_name' => $guard]);
        Role::create(['name' => RoleEnum::User->value, 'guard_name' => $guard]);
    }

    $admin = loginAsUser();
    $perm = Permission::firstOrCreate(['name' => PermissionEnum::RoleView->value, 'guard_name' => 'sanctum']);
    $admin->givePermissionTo($perm);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin->update(['name' => 'Admin User']);

    Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);
    Role::create(['name' => 'viewer', 'guard_name' => 'sanctum']);
});

describe('Roles Filtering', function () {
    it('filters by name using LIKE partial match', function () {
        Role::create(['name' => 'moderator', 'guard_name' => 'sanctum']);

        $response = $this->getJson('/api/v1/roles?filter[name]=editor');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('editor');
    })->group('v1', 'filter');
});

describe('Roles Search', function () {
    it('searches across name and description', function () {
        Role::create(['name' => 'moderator', 'description' => 'Content moderation', 'guard_name' => 'sanctum']);

        $response = $this->getJson('/api/v1/roles?search=moderator');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('moderator');
    })->group('v1', 'filter');
});

describe('Roles Sorting', function () {
    it('sorts ascending by name', function () {
        $response = $this->getJson('/api/v1/roles?sort=name');

        $names = collect($response->json('data'))->pluck('name')->all();
        expect($names[0])->toBe('admin')
            ->and($names[count($names) - 1])->toBe('viewer');
    })->group('v1', 'filter');

    it('sorts descending with minus prefix', function () {
        $response = $this->getJson('/api/v1/roles?sort=-name');

        $names = collect($response->json('data'))->pluck('name')->all();
        expect($names[0])->toBe('viewer')
            ->and($names[count($names) - 1])->toBe('admin');
    })->group('v1', 'filter');
});

describe('Roles Pagination', function () {
    it('paginates results with default per page', function () {
        $response = $this->getJson('/api/v1/roles');

        expect($response->json('meta.per_page'))->toBe(15);
    })->group('v1', 'filter');

    it('respects requested page size', function () {
        $response = $this->getJson('/api/v1/roles?page[size]=2');

        expect($response->json('data'))->toHaveCount(2)
            ->and($response->json('meta.per_page'))->toBe(2);
    })->group('v1', 'filter');

    it('rejects page size exceeding 100', function () {
        $response = $this->getJson('/api/v1/roles?page[size]=200');

        $response->assertStatus(422);
    })->group('v1', 'filter');
});

describe('Roles Sparse Fields', function () {
    it('selects only requested fields', function () {
        $response = $this->getJson('/api/v1/roles?fields[roles]=id,name');

        expect($response->json('data.0'))->toHaveKeys(['id', 'name']);
    })->group('v1', 'filter');
});

describe('Roles Includes', function () {
    it('eager loads included permissions relation', function () {
        $perm = Permission::firstOrCreate(['name' => 'test.perm', 'guard_name' => 'sanctum']);
        $role = Role::where('name', RoleEnum::Admin->value)->where('guard_name', 'sanctum')->first();
        $role?->givePermissionTo($perm);

        $response = $this->getJson('/api/v1/roles?include=permissions');

        expect($response->json('data.0.permissions'))->toBeArray();
    })->group('v1', 'filter');
});
