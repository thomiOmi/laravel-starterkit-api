<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Contracts\Auth\Factory;
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

    it('revokes access tokens when an admin deletes a user and keeps them revoked after restore', function (): void {
        Permission::query()->firstOrCreate(['name' => PermissionEnum::UserRestore->value, 'guard_name' => 'sanctum']);

        $actor = UserFactory::new()->createOne();
        $actor->givePermissionTo([
            PermissionEnum::UserDelete->value,
            PermissionEnum::UserRestore->value,
        ]);
        $actorToken = $actor->createToken('admin-device');

        $target = UserFactory::new()->createOne();
        $targetToken = $target->createToken('stolen-device');

        $deleteResponse = $this->withHeader('Authorization', 'Bearer '.$actorToken->plainTextToken)
            ->deleteJson("/api/v1/users/{$target->id}");

        assertSuccessResponse($deleteResponse, 200);
        expect($target->tokens()->count())->toBe(0);

        resolve(Factory::class)->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$targetToken->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        $restoreResponse = $this->withHeader('Authorization', 'Bearer '.$actorToken->plainTextToken)
            ->postJson('/api/v1/users/bulk/restore', ['ids' => [$target->id]]);

        assertSuccessResponse($restoreResponse, 200);
        expect($target->refresh()->deleted_at)->toBeNull();

        resolve(Factory::class)->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$targetToken->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
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
