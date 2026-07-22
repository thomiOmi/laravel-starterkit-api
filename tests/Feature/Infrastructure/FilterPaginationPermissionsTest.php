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
    $perm = Permission::firstOrCreate(['name' => PermissionEnum::PermissionView->value, 'guard_name' => 'sanctum']);
    $admin->givePermissionTo($perm);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin->update(['name' => 'Admin User']);

    Permission::create(['name' => 'user.create', 'guard_name' => 'sanctum']);
    Permission::create(['name' => 'user.edit', 'guard_name' => 'sanctum']);
    Permission::create(['name' => 'user.delete', 'guard_name' => 'sanctum']);
});

describe('Permissions Filtering', function () {
    it('filters by name using LIKE partial match', function () {
        $response = $this->getJson('/api/v1/permissions?filter[name]=edit');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('user.edit');
    })->group('v1', 'filter');
});

describe('Permissions Search', function () {
    it('searches across name', function () {
        $response = $this->getJson('/api/v1/permissions?search=delete');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('user.delete');
    })->group('v1', 'filter');
});

describe('Permissions Sorting', function () {
    it('sorts ascending by name', function () {
        $response = $this->getJson('/api/v1/permissions?sort=name');

        $names = collect($response->json('data'))->pluck('name')->all();
        expect($names[0])->toBe('permission.view')
            ->and($names[count($names) - 1])->toBe('user.edit');
    })->group('v1', 'filter');

    it('sorts descending with minus prefix', function () {
        $response = $this->getJson('/api/v1/permissions?sort=-name');

        $names = collect($response->json('data'))->pluck('name')->all();
        expect($names[0])->toBe('user.edit')
            ->and($names[count($names) - 1])->toBe('permission.view');
    })->group('v1', 'filter');
});

describe('Permissions Pagination', function () {
    it('paginates results with default per page', function () {
        $response = $this->getJson('/api/v1/permissions');

        expect($response->json('meta.per_page'))->toBe(10);
    })->group('v1', 'filter');

    it('respects requested page size', function () {
        $response = $this->getJson('/api/v1/permissions?page[size]=2');

        expect($response->json('data'))->toHaveCount(2);
    })->group('v1', 'filter');

    it('rejects page size exceeding 100', function () {
        $response = $this->getJson('/api/v1/permissions?page[size]=200');

        $response->assertStatus(422);
    })->group('v1', 'filter');
});

describe('Permissions Sparse Fields', function () {
    it('selects only requested fields', function () {
        $response = $this->getJson('/api/v1/permissions?fields[permissions]=id,name');

        expect($response->json('data.0'))->toHaveKeys(['id', 'name']);
    })->group('v1', 'filter');
});
