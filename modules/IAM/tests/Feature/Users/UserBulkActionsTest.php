<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\UserAssignRolesController;
use Modules\IAM\Http\Controllers\V1\UserBulkDeleteController;
use Modules\IAM\Http\Controllers\V1\UserBulkRestoreController;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

covers([
    UserBulkDeleteController::class,
    UserBulkRestoreController::class,
    UserAssignRolesController::class,
]);

describe('POST /api/v1/users/bulk/delete', function () {
    beforeEach(function () {
        Permission::firstOrCreate(['name' => PermissionEnum::UserDelete->value, 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => PermissionEnum::UserRestore->value, 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);
    });

    it('soft-deletes the given users and skips super-admins', function () {
        $actor = loginAsUser();
        $actor->givePermissionTo(PermissionEnum::UserDelete->value);
        $victim = UserFactory::new()->createOne();
        $superAdmin = UserFactory::new()->superAdmin()->createOne();

        $response = $this->postJson('/api/v1/users/bulk/delete', ['ids' => [
            $victim->id, $superAdmin->id,
        ]]);

        assertSuccessResponse($response, 200);
        expect($victim->refresh()->deleted_at)->not->toBeNull()
            ->and($superAdmin->refresh()->deleted_at)->toBeNull();
    });

    it('validates the ids payload', function () {
        $actor = loginAsUser();
        $actor->givePermissionTo(PermissionEnum::UserDelete->value);

        $response = $this->postJson('/api/v1/users/bulk/delete', ['ids' => []]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['ids']);
    });
});

describe('POST /api/v1/users/bulk/restore', function () {
    beforeEach(function () {
        Permission::firstOrCreate(['name' => PermissionEnum::UserRestore->value, 'guard_name' => 'sanctum']);
    });

    it('restores soft-deleted users', function () {
        $actor = loginAsUser();
        $actor->givePermissionTo(PermissionEnum::UserRestore->value);
        $trashed = UserFactory::new()->createOne();
        $trashed->delete();

        $response = $this->postJson('/api/v1/users/bulk/restore', ['ids' => [$trashed->id]]);

        assertSuccessResponse($response, 200);
        expect($trashed->refresh()->deleted_at)->toBeNull();
    });
});

describe('PUT /api/v1/users/{user}/roles', function () {
    beforeEach(function () {
        Permission::firstOrCreate(['name' => PermissionEnum::UserEdit->value, 'guard_name' => 'sanctum']);
        foreach ([RoleEnum::SuperAdmin, RoleEnum::Admin, RoleEnum::User] as $role) {
            Role::firstOrCreate(['name' => $role->value, 'guard_name' => 'sanctum']);
        }
    });

    it('syncs roles on a non-super-admin target', function () {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::UserEdit->value);
        $target = UserFactory::new()->createOne();

        $response = $this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => [RoleEnum::User->value, RoleEnum::Admin->value],
        ]);

        assertSuccessResponse($response, 200);
        expect($target->refresh()->hasRole(RoleEnum::User->value))->toBeTrue()
            ->and($target->refresh()->hasRole(RoleEnum::Admin->value))->toBeTrue()
            ->and($target->refresh()->hasRole(RoleEnum::SuperAdmin->value))->toBeFalse();
    });

    it('blocks assigning super-admin without being one', function () {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::UserEdit->value);
        $target = UserFactory::new()->createOne();

        assertProblemResponse($this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => [RoleEnum::SuperAdmin->value],
        ]), 403);
    });

    it('rejects unknown role names', function () {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::UserEdit->value);
        $target = UserFactory::new()->createOne();

        $response = $this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => ['ghost-role'],
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['roles.0']);
    });
});
