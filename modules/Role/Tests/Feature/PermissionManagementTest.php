<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->admin = loginAsUser();
    Permission::create(['name' => 'permission.view', 'guard_name' => 'web']);
    Permission::create(['name' => 'permission.create', 'guard_name' => 'web']);
    $this->admin->givePermissionTo(['permission.view', 'permission.create']);
});

describe('Permission Management', function () {
    it('lists permissions', function () {
        $this->getJson('/api/v1/permissions')
            ->toBeSuccessResponse();
    })->group('v1');
});
