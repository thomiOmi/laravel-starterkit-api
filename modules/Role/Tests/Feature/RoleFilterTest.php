<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Role\Models\Role;
use Tests\Helpers\WithAdminUser;

uses(WithAdminUser::class);

beforeEach(function () {
    $this->setUpAdminUser();
});

describe('RoleFilter search', function () {
    it('finds roles by name with single keyword', function () {
        Role::create(['name' => 'developer', 'guard_name' => 'web']);

        $this->adminGet('/api/v1/roles?search=developer')
            ->assertJson(fn (AssertableJson $json) => $json
                ->count('data', 1)
                ->etc()
            );
    });

    it('returns empty results for non-matching search', function () {
        $this->adminGet('/api/v1/roles?search=zzzzzzz')
            ->assertJson(fn (AssertableJson $json) => $json
                ->count('data', 0)
                ->etc()
            );
    });
});

describe('RoleFilter sort', function () {
    it('sorts roles by name ascending', function () {
        $this->adminGet('/api/v1/roles?sort=name')
            ->assertJsonPath('data.0.name', 'admin')
            ->assertJsonPath('data.2.name', 'super-admin')
            ->assertJsonPath('data.4.name', 'user');
    });

    it('sorts roles by name descending', function () {
        $this->adminGet('/api/v1/roles?sort=-name')
            ->assertJsonPath('data.0.name', 'user')
            ->assertJsonPath('data.2.name', 'super-admin')
            ->assertJsonPath('data.4.name', 'admin');
    });

    it('applies default sort when no sort parameter', function () {
        $this->adminGet('/api/v1/roles')
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
