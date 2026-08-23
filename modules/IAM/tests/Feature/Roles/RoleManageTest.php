<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Modules\IAM\Http\Controllers\V1\RoleBulkDeleteController;
use Modules\IAM\Http\Controllers\V1\RoleDeleteController;
use Modules\IAM\Http\Controllers\V1\RoleShowController;
use Modules\IAM\Http\Controllers\V1\RoleUpdateController;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

covers([
    RoleShowController::class,
    RoleUpdateController::class,
    RoleDeleteController::class,
    RoleBulkDeleteController::class,
]);

describe('role management guards', function () {
    beforeEach(function () {
        foreach ([PermissionEnum::RoleView, PermissionEnum::RoleEdit, PermissionEnum::RoleDelete] as $permission) {
            Permission::firstOrCreate(['name' => $permission->value, 'guard_name' => 'sanctum']);
        }
        Role::firstOrCreate(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum', 'description' => 'Keeper']);
        Role::firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
    });

    it('blocks updating the super-admin role', function () {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::RoleEdit->value);
        $superAdmin = Role::query()->where('name', RoleEnum::SuperAdmin->value)->firstOrFail();

        assertProblemResponse($this->putJson("/api/v1/roles/{$superAdmin->id}", [
            'name' => 'renamed-super',
        ]), 403);
    });

    it('updates a normal role with the edit permission', function () {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::RoleEdit->value);
        $role = Role::query()->where('name', RoleEnum::User->value)->firstOrFail();

        assertSuccessResponse(
            $this->putJson("/api/v1/roles/{$role->id}", ['name' => 'user', 'description' => 'Regular folks']),
            200
        );
    });

    it('blocks deleting the super-admin role and permits deleting others', function () {
        $actor = loginAsUser();
        $actor->givePermissionTo(PermissionEnum::RoleDelete->value);
        $superAdmin = Role::query()->where('name', RoleEnum::SuperAdmin->value)->firstOrFail();
        $userRole = Role::query()->where('name', RoleEnum::User->value)->firstOrFail();

        assertProblemResponse($this->deleteJson("/api/v1/roles/{$superAdmin->id}"), 403);

        assertSuccessResponse($this->deleteJson("/api/v1/roles/{$userRole->id}"), 200);
        expect(Role::query()->whereKey($userRole->id)->exists())->toBeFalse();
    });
});
