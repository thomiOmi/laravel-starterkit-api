<?php

declare(strict_types=1);

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
            ->assertJsonCount(1, 'data');
    });

    it('returns empty results for non-matching search', function () {
        $this->adminGet('/api/v1/roles?search=zzzzzzz')
            ->assertJsonCount(0, 'data');
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
        $this->adminGet('/api/v1/roles');
    });
});
