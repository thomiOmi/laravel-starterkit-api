<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use App\Enums\PermissionEnum;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

beforeEach(function () {
    $this->admin = loginAsUser();
    Permission::firstOrCreate(['name' => PermissionEnum::RoleEdit->value, 'guard_name' => 'sanctum']);
    Permission::firstOrCreate(['name' => PermissionEnum::RoleDelete->value, 'guard_name' => 'sanctum']);
    $this->admin->givePermissionTo([PermissionEnum::RoleEdit, PermissionEnum::RoleDelete]);
});

describe('Role Bulk Operations', function () {
    it('fails bulk delete with missing ids', function () {
        expect($this->postJson('/api/v1/roles/bulk/delete', []))
            ->toBeProblemResponse(status: 422);
    })->group('v1');

    it('can bulk delete roles', function () {
        $roles = [
            Role::create(['name' => 'r1', 'guard_name' => 'sanctum']),
            Role::create(['name' => 'r2', 'guard_name' => 'sanctum']),
        ];
        $ids = collect($roles)->pluck('id')->toArray();

        expect($this->postJson('/api/v1/roles/bulk/delete', ['ids' => $ids]))
            ->toBeSuccessResponse();

        foreach ($roles as $role) {
            expect($role->fresh())->toBeNull();
        }
    })->group('v1');
});
