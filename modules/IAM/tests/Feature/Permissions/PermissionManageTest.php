<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Modules\IAM\Http\Controllers\V1\PermissionDeleteController;
use Modules\IAM\Http\Controllers\V1\PermissionListController;
use Modules\IAM\Http\Controllers\V1\PermissionShowController;
use Modules\IAM\Http\Controllers\V1\PermissionUpdateController;
use Modules\IAM\Models\Permission;

covers([
    PermissionListController::class,
    PermissionShowController::class,
    PermissionUpdateController::class,
    PermissionDeleteController::class,
]);

describe('permission management', function () {
    beforeEach(function () {
        foreach ([PermissionEnum::PermissionView, PermissionEnum::PermissionEdit, PermissionEnum::PermissionDelete] as $permission) {
            Permission::firstOrCreate(['name' => $permission->value, 'guard_name' => 'sanctum']);
        }

        Permission::firstOrCreate(['name' => 'old.name', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'taken.name', 'guard_name' => 'sanctum']);
    });

    it('lists permissions paginated with the view permission', function () {
        $viewer = loginAsUser();
        $viewer->givePermissionTo(PermissionEnum::PermissionView->value);

        $response = $this->getJson('/api/v1/permissions?page[size]=10&page[number]=1');

        assertSuccessResponse($response, 200);
        assertPaginatedResponse($response);
    });

    it('rejects listing without the view permission', function () {
        loginAsUser();

        assertProblemResponse($this->getJson('/api/v1/permissions'), 403);
    });

    it('shows a permission with the view permission', function () {
        $viewer = loginAsUser();
        $viewer->givePermissionTo(PermissionEnum::PermissionView->value);
        $permission = Permission::where('name', 'old.name')->firstOrFail();

        $response = $this->getJson("/api/v1/permissions/{$permission->id}");

        assertSuccessResponse($response, 200);
        expect($response->json('data.name'))->toBe('old.name');
    });

    it('rejects showing without the view permission', function () {
        loginAsUser();
        $permission = Permission::where('name', 'old.name')->firstOrFail();

        assertProblemResponse($this->getJson("/api/v1/permissions/{$permission->id}"), 403);
    });

    it('renames a permission with the edit permission', function () {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::PermissionEdit->value);
        $permission = Permission::where('name', 'old.name')->firstOrFail();

        $response = $this->putJson("/api/v1/permissions/{$permission->id}", ['name' => 'new.name']);

        assertSuccessResponse($response, 200);
        expect($response->json('data.name'))->toBe('new.name');
    });

    it('rejects duplicate names on update', function () {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::PermissionEdit->value);
        $permission = Permission::where('name', 'old.name')->firstOrFail();

        $response = $this->putJson("/api/v1/permissions/{$permission->id}", ['name' => 'taken.name']);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['name']);
    });

    it('rejects renaming without the edit permission', function () {
        loginAsUser();
        $permission = Permission::where('name', 'old.name')->firstOrFail();

        assertProblemResponse($this->putJson("/api/v1/permissions/{$permission->id}", ['name' => 'x']), 403);
    });

    it('deletes a permission with the delete permission', function () {
        $actor = loginAsUser();
        $actor->givePermissionTo(PermissionEnum::PermissionDelete->value);
        $permission = Permission::where('name', 'old.name')->firstOrFail();

        assertSuccessResponse($this->deleteJson("/api/v1/permissions/{$permission->id}"), 200);
        expect(Permission::query()->whereKey($permission->id)->exists())->toBeFalse();
    });

    it('rejects deleting without the delete permission', function () {
        loginAsUser();
        $permission = Permission::where('name', 'old.name')->firstOrFail();

        assertProblemResponse($this->deleteJson("/api/v1/permissions/{$permission->id}"), 403);
    });
});
