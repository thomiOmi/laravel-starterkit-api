<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\UserUpdateController;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

covers(UserUpdateController::class);

describe('PUT /api/v1/users/{user}', function () {
    beforeEach(function () {
        Permission::firstOrCreate(['name' => PermissionEnum::UserEdit->value, 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
    });

    it('allows self updates without permission', function () {
        $user = loginAsUser();

        $response = $this->putJson("/api/v1/users/{$user->id}", ['name' => 'Renamed Self', 'email' => $user->email]);

        assertSuccessResponse($response, 200);
        expect($response->json('data.name'))->toBe('Renamed Self');
    });

    it('permits editors to update others', function () {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::UserEdit->value);
        $target = UserFactory::new()->createOne();

        assertSuccessResponse(
            $this->putJson("/api/v1/users/{$target->id}", ['name' => 'Edited', 'email' => $target->email]),
            200
        );
    });

    it('blocks editing super-admins even with permission', function () {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::UserEdit->value);
        $superAdmin = UserFactory::new()->superAdmin()->createOne();

        assertProblemResponse($this->putJson("/api/v1/users/{$superAdmin->id}", ['name' => 'Hijack']), 403);
    });

    it('prohibits status changes without the edit permission', function () {
        $user = loginAsUser();

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => $user->name,
            'status' => UserStatusEnum::Banned->value,
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['status']);
    });
});
