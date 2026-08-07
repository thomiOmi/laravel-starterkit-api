<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\PermissionFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\Permission;

describe('PermissionPolicy', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);
    });

    describe('view', function (): void {
        it('allows a user with the view permission', function (): void {
            $user = loginAsAdmin();
            $permission = Permission::firstOrFail();

            expect($user->can('view', $permission))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the view permission', function (): void {
            $user = loginAsUserRole();
            $permission = Permission::firstOrFail();

            expect($user->can('view', $permission))->toBeFalse();
        })->group('module:iam');
    });

    describe('create', function (): void {
        it('allows a user with the create permission', function (): void {
            $user = loginAsAdmin();

            expect($user->can('create', Permission::class))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the create permission', function (): void {
            $user = loginAsUserRole();

            expect($user->can('create', Permission::class))->toBeFalse();
        })->group('module:iam');
    });

    describe('update', function (): void {
        it('allows a user with the edit permission', function (): void {
            $user = loginAsAdmin();
            $permission = Permission::firstOrFail();

            expect($user->can('update', $permission))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the edit permission', function (): void {
            $user = loginAsUserRole();
            $permission = Permission::firstOrFail();

            expect($user->can('update', $permission))->toBeFalse();
        })->group('module:iam');
    });

    describe('delete', function (): void {
        it('allows a user with the delete permission', function (): void {
            $user = loginAsAdmin();
            $permission = PermissionFactory::new()->createOne();

            expect($user->can('delete', $permission))->toBeTrue();
        })->group('module:iam');

        it('denies a user without the delete permission', function (): void {
            $user = loginAsUserRole();
            $permission = PermissionFactory::new()->createOne();

            expect($user->can('delete', $permission))->toBeFalse();
        })->group('module:iam');
    });

    describe('route enforcement', function (): void {
        it('returns 403 when updating a permission without the edit permission', function (): void {
            loginAsUserRole();
            $permission = Permission::firstOrFail();

            $response = $this->putJson("/api/v1/permissions/{$permission->id}", [
                'name' => 'some.permission',
            ]);

            assertProblemResponse($response, 403);
        })->group('module:iam');

        it('allows an admin to delete a permission', function (): void {
            loginAsAdmin();
            $permission = PermissionFactory::new()->createOne();

            $response = $this->deleteJson("/api/v1/permissions/{$permission->id}");

            assertSuccessResponse($response, 200, 'Permission deleted successfully');
            expect(Permission::query()->whereKey($permission->id)->exists())->toBeFalse();
        })->group('module:iam');
    });
});
