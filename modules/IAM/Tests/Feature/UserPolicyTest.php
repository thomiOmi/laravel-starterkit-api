<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;

describe('UserPolicy', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);
    });

    describe('view', function (): void {
        it('allows a user to view themselves', function (): void {
            $user = loginAsUser();

            expect($user->can('view', $user))->toBeTrue();
        })->group('module:iam');

        it('allows a user with the view permission to view other users', function (): void {
            $user = loginAsAdmin();
            $target = UserFactory::new()->createOne();

            expect($user->can('view', $target))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the view permission', function (): void {
            $user = loginAsUser();
            $target = UserFactory::new()->createOne();

            expect($user->can('view', $target))->toBeFalse();
        })->group('module:iam');
    });

    describe('create', function (): void {
        it('allows a user with the create permission', function (): void {
            $user = loginAsAdmin();

            expect($user->can('create', User::class))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the create permission', function (): void {
            $user = loginAsUserRole();

            expect($user->can('create', User::class))->toBeFalse();
        })->group('module:iam');

        it('allows a super admin through the gate bypass', function (): void {
            $user = loginAsSuperAdmin();

            expect($user->can('create', User::class))->toBeTrue();
        })->group('module:iam');
    });

    describe('update', function (): void {
        it('allows a user to update themselves', function (): void {
            $user = loginAsUser();

            expect($user->can('update', $user))->toBeTrue();
        })->group('module:iam');

        it('allows a user with the edit permission to update others', function (): void {
            $user = loginAsAdmin();
            $target = UserFactory::new()->createOne();

            expect($user->can('update', $target))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the edit permission', function (): void {
            $user = loginAsUser();
            $target = UserFactory::new()->createOne();

            expect($user->can('update', $target))->toBeFalse();
        })->group('module:iam');

        it('denies editing a super admin', function (): void {
            $user = loginAsAdmin();
            $superAdmin = UserFactory::new()->superAdmin()->createOne();

            expect($user->can('update', $superAdmin))->toBeFalse();
        })->group('module:iam');

        it('allows a super admin to update another super admin', function (): void {
            $user = loginAsSuperAdmin();
            $superAdmin = UserFactory::new()->superAdmin()->createOne();

            expect($user->can('update', $superAdmin))->toBeTrue();
        })->group('module:iam');
    });

    describe('delete', function (): void {
        it('denies deleting yourself', function (): void {
            $user = loginAsAdmin();

            expect($user->can('delete', $user))->toBeFalse();
        })->group('module:iam');

        it('allows a user with the delete permission to delete others', function (): void {
            $user = loginAsAdmin();
            $target = UserFactory::new()->createOne();

            expect($user->can('delete', $target))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the delete permission', function (): void {
            $user = loginAsUser();
            $target = UserFactory::new()->createOne();

            expect($user->can('delete', $target))->toBeFalse();
        })->group('module:iam');

        it('denies deleting a super admin', function (): void {
            $user = loginAsAdmin();
            $superAdmin = UserFactory::new()->superAdmin()->createOne();

            expect($user->can('delete', $superAdmin))->toBeFalse();
        })->group('module:iam');

        it('allows a super admin to delete another super admin', function (): void {
            $user = loginAsSuperAdmin();
            $superAdmin = UserFactory::new()->superAdmin()->createOne();

            expect($user->can('delete', $superAdmin))->toBeTrue();
        })->group('module:iam');
    });

    describe('route enforcement', function (): void {
        it('returns 403 when updating another user without the edit permission', function (): void {
            loginAsUser();
            $target = UserFactory::new()->createOne();

            $response = $this->putJson("/api/v1/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
            ]);

            assertProblemResponse($response, 403, 'access-denied');
        })->group('module:iam');

        it('returns 403 when deleting another user without the delete permission', function (): void {
            loginAsUser();
            $target = UserFactory::new()->createOne();

            $response = $this->deleteJson("/api/v1/users/{$target->id}");

            assertProblemResponse($response, 403);
        })->group('module:iam');

        it('returns 403 when a non super admin deletes a super admin', function (): void {
            loginAsAdmin();
            $superAdmin = UserFactory::new()->superAdmin()->createOne();

            $response = $this->deleteJson("/api/v1/users/{$superAdmin->id}");

            assertProblemResponse($response, 403, 'access-denied');
        })->group('module:iam');

        it('returns 403 when the permission is granted on a different guard', function (): void {
            $webRole = Role::create(['name' => 'web-editor', 'guard_name' => 'web']);
            $webRole->givePermissionTo(Permission::create(['name' => 'user.edit', 'guard_name' => 'web']));

            $user = UserFactory::new()->createOne();
            $user->assignRole($webRole);

            loginAsUser($user);

            $target = UserFactory::new()->createOne();

            $response = $this->putJson("/api/v1/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
            ]);

            assertProblemResponse($response, 403, 'access-denied');
        })->group('module:iam');
    });
});
