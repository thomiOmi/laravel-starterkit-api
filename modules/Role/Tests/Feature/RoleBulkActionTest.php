<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Modules\Role\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->admin = loginAsUser();
    Permission::create(['name' => 'role.delete', 'guard_name' => 'web']);
    $this->admin->givePermissionTo('role.delete');
});

describe('Role Bulk Actions', function () {
    it('bulk deletes roles', function () {
        $role = Role::create(['name' => 'delete-me', 'guard_name' => 'web']);

        $this->postJson('/api/v1/roles/bulk/delete', ['ids' => [$role->id]])
            ->toBeSuccessResponse();

        expect($role->fresh()->trashed())->toBeTrue();
    })->group('v1');
});
