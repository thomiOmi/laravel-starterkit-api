<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Modules\IAM\Database\Factories\RoleFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\Role;

describe('RolePolicy', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);
    });

    describe('view', function (): void {
        it('allows a user with the view permission', function (): void {
            $user = loginAsAdmin();
            $role = Role::query()->where('name', RoleEnum::Admin->value)->firstOrFail();

            expect($user->can('view', $role))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the view permission', function (): void {
            $user = loginAsUserRole();
            $role = Role::query()->where('name', RoleEnum::Admin->value)->firstOrFail();

            expect($user->can('view', $role))->toBeFalse();
        })->group('module:iam');
    });

    describe('create', function (): void {
        it('allows a user with the create permission', function (): void {
            $user = loginAsAdmin();

            expect($user->can('create', Role::class))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the create permission', function (): void {
            $user = loginAsUserRole();

            expect($user->can('create', Role::class))->toBeFalse();
        })->group('module:iam');
    });

    describe('update', function (): void {
        it('allows updating a non super admin role', function (): void {
            $user = loginAsAdmin();
            $role = RoleFactory::new()->createOne(['name' => 'manager']);

            expect($user->can('update', $role))->toBeTrue();
        })->group('module:iam');

        it('denies updating the super admin role', function (): void {
            $user = loginAsAdmin();
            $superAdminRole = Role::query()->where('name', RoleEnum::SuperAdmin->value)->firstOrFail();

            expect($user->can('update', $superAdminRole))->toBeFalse();
        })->group('module:iam');

        it('allows a super admin to update the super admin role', function (): void {
            $user = loginAsSuperAdmin();
            $superAdminRole = Role::query()->where('name', RoleEnum::SuperAdmin->value)->firstOrFail();

            expect($user->can('update', $superAdminRole))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the edit permission', function (): void {
            $user = loginAsUserRole();
            $role = RoleFactory::new()->createOne(['name' => 'manager']);

            expect($user->can('update', $role))->toBeFalse();
        })->group('module:iam');
    });

    describe('delete', function (): void {
        it('allows deleting a non super admin role', function (): void {
            $user = loginAsAdmin();
            $role = RoleFactory::new()->createOne(['name' => 'manager']);

            expect($user->can('delete', $role))->toBeTrue();
        })->group('module:iam');

        it('denies deleting the super admin role', function (): void {
            $user = loginAsAdmin();
            $superAdminRole = Role::query()->where('name', RoleEnum::SuperAdmin->value)->firstOrFail();

            expect($user->can('delete', $superAdminRole))->toBeFalse();
        })->group('module:iam');

        it('allows a super admin to delete the super admin role', function (): void {
            $user = loginAsSuperAdmin();
            $superAdminRole = Role::query()->where('name', RoleEnum::SuperAdmin->value)->firstOrFail();

            expect($user->can('delete', $superAdminRole))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the delete permission', function (): void {
            $user = loginAsUserRole();
            $role = RoleFactory::new()->createOne(['name' => 'manager']);

            expect($user->can('delete', $role))->toBeFalse();
        })->group('module:iam');
    });

    describe('route enforcement', function (): void {
        it('returns 403 when updating the super admin role', function (): void {
            loginAsAdmin();
            $superAdminRole = Role::query()->where('name', RoleEnum::SuperAdmin->value)->firstOrFail();

            $response = $this->putJson("/api/v1/roles/{$superAdminRole->id}", [
                'name' => RoleEnum::SuperAdmin->value,
            ]);

            assertProblemResponse($response, 403, 'access-denied');
        })->group('module:iam');

        it('returns 403 when deleting the super admin role', function (): void {
            loginAsAdmin();
            $superAdminRole = Role::query()->where('name', RoleEnum::SuperAdmin->value)->firstOrFail();

            $response = $this->deleteJson("/api/v1/roles/{$superAdminRole->id}");

            assertProblemResponse($response, 403, 'access-denied');
        })->group('module:iam');

        it('allows an admin to delete a non super admin role', function (): void {
            loginAsAdmin();
            $role = RoleFactory::new()->createOne(['name' => 'manager']);

            $response = $this->deleteJson("/api/v1/roles/{$role->id}");

            assertSuccessResponse($response, 200, 'Role deleted successfully');
            expect(Role::query()->whereKey($role->id)->exists())->toBeFalse();
        })->group('module:iam');
    });
});
