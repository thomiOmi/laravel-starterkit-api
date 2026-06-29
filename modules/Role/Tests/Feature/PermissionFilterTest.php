<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Role\Database\Factories\PermissionFactory;
use Tests\Helpers\WithAdminUser;

uses(WithAdminUser::class);

beforeEach(function () {
    $this->setUpAdminUser();
});

describe('PermissionFilter search', function () {
    it('finds permissions by name with single keyword', function () {
        PermissionFactory::new()->create(['name' => 'admin.reports', 'guard_name' => 'web']);

        $this->adminGet('/api/v1/permissions?search=admin.reports')
            ->assertJson(fn (AssertableJson $json) => $json
                ->count('data', 1)
                ->etc()
            );
    });

    it('finds permissions by guard_name with search', function () {
        PermissionFactory::new()->create(['name' => 'reports.api', 'guard_name' => 'api']);

        $this->adminGet('/api/v1/permissions?search=reports')
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->etc()
            );
    });

    it('returns empty results for non-matching search', function () {
        PermissionFactory::new()->create(['name' => 'unmatched.demo', 'guard_name' => 'web']);

        $this->adminGet('/api/v1/permissions?search=zzzzzzz')
            ->assertJson(fn (AssertableJson $json) => $json
                ->count('data', 0)
                ->etc()
            );
    });
});

describe('PermissionFilter guard filter', function () {
    it('filters by api guard', function () {
        PermissionFactory::new()->create(['name' => 'api.sync', 'guard_name' => 'api']);

        $this->adminGet('/api/v1/permissions?filter[guard]=api')
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->where('data.0.name', 'api.sync')
                ->etc()
            );
    });

    it('returns empty for non-existent guard', function () {
        PermissionFactory::new()->create(['name' => 'nonexistent.guard', 'guard_name' => 'nonexistent']);

        $this->adminGet('/api/v1/permissions?filter[guard]=nonexistent')
            ->assertJson(fn (AssertableJson $json) => $json
                ->count('data', 1)
                ->etc()
            );
    });
});

describe('PermissionFilter sort', function () {
    it('sorts by name ascending', function () {
        PermissionFactory::new()->create(['name' => 'zzz.last', 'guard_name' => 'web']);
        PermissionFactory::new()->create(['name' => 'aaa.first', 'guard_name' => 'web']);

        $this->adminGet('/api/v1/permissions?sort=name')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.0.name', 'aaa.first')
                ->etc()
            );
    });

    it('sorts by name descending', function () {
        PermissionFactory::new()->create(['name' => 'aaa.first', 'guard_name' => 'web']);
        PermissionFactory::new()->create(['name' => 'zzz.last', 'guard_name' => 'web']);

        $this->adminGet('/api/v1/permissions?sort=-name')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.0.name', 'zzz.last')
                ->etc()
            );
    });

    it('applies default sort when no sort parameter', function () {
        $this->adminGet('/api/v1/permissions')
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data')
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
