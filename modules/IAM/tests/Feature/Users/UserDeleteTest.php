<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\UserDeleteController;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

covers(UserDeleteController::class);

describe('DELETE /api/v1/users/{user}', function (): void {
    beforeEach(function (): void {
        Permission::query()->firstOrCreate(['name' => PermissionEnum::UserDelete->value, 'guard_name' => 'sanctum']);
        Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);
    });

    it('soft-deletes another user with the delete permission', function (): void {
        $actor = loginAsUser();
        $actor->givePermissionTo(PermissionEnum::UserDelete->value);

        $target = UserFactory::new()->createOne();

        assertSuccessResponse($this->deleteJson("/api/v1/users/{$target->id}"), 200);
        expect($target->refresh()->deleted_at)->not->toBeNull();
    });

    it('blocks deleting yourself even with permission', function (): void {
        $actor = loginAsUser();
        $actor->givePermissionTo(PermissionEnum::UserDelete->value);

        assertProblemResponse($this->deleteJson("/api/v1/users/{$actor->id}"), 403);
        expect($actor->refresh()->deleted_at)->toBeNull();
    });

    it('blocks deleting super-admins', function (): void {
        $actor = loginAsUser();
        $actor->givePermissionTo(PermissionEnum::UserDelete->value);

        $superAdmin = UserFactory::new()->superAdmin()->createOne();

        assertProblemResponse($this->deleteJson("/api/v1/users/{$superAdmin->id}"), 403);
        expect($superAdmin->refresh()->deleted_at)->toBeNull();
    });

    it('rejects users without the delete permission', function (): void {
        loginAsUser();
        $target = UserFactory::new()->createOne();

        assertProblemResponse($this->deleteJson("/api/v1/users/{$target->id}"), 403);
    });
});
