<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Http\Controllers\V1\UserListController;
use Modules\IAM\Http\Controllers\V1\UserShowController;
use Modules\IAM\Http\Controllers\V1\UserUpdateController;
use Modules\IAM\Models\Role;

covers([UserListController::class, UserShowController::class, UserUpdateController::class]);

describe('seeded user role read scope', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);
    });

    it('does not grant cross-owner read permissions to the user role', function (): void {
        $role = Role::findByName(RoleEnum::User->value, 'sanctum');

        expect($role->hasPermissionTo(PermissionEnum::UserView->value))->toBeFalse()
            ->and($role->hasPermissionTo(PermissionEnum::MediaView->value))->toBeFalse()
            ->and($role->hasPermissionTo(PermissionEnum::MediaCreate->value))->toBeTrue();
    });

    it('rejects listing every user', function (): void {
        loginAsUserRole();

        assertProblemResponse($this->getJson('/api/v1/users'), 403);
    });

    it('rejects reading another user profile', function (): void {
        loginAsUserRole();
        $other = UserFactory::new()->user()->createOne();

        assertProblemResponse($this->getJson("/api/v1/users/{$other->id}"), 403);
    });

    it('keeps self read and self update working', function (): void {
        $user = loginAsUserRole();

        $show = $this->getJson("/api/v1/users/{$user->id}");

        assertSuccessResponse($show, 200);
        expect($show->json('data.id'))->toBe($user->id);

        $update = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => 'Renamed Self',
            'email' => $user->email,
        ]);

        assertSuccessResponse($update, 200);
        expect($update->json('data.name'))->toBe('Renamed Self');
    });

    it('still rejects writing another user profile', function (): void {
        loginAsUserRole();
        $other = UserFactory::new()->user()->createOne();

        assertProblemResponse(
            $this->putJson("/api/v1/users/{$other->id}", [
                'name' => 'Hijack',
                'email' => $other->email,
            ]),
            403
        );
    });

    it('keeps admin access to the user directory', function (): void {
        loginAsAdmin();
        $other = UserFactory::new()->user()->createOne();

        assertSuccessResponse($this->getJson('/api/v1/users'), 200);
        assertSuccessResponse($this->getJson("/api/v1/users/{$other->id}"), 200);
    });
});
