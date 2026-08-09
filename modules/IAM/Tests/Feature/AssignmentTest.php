<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;

describe('role assignment', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);
    });

    it('assigns roles to a user', function (): void {
        loginAsSuperAdmin();
        $target = UserFactory::new()->createOne();

        $response = $this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => ['admin', 'user'],
        ]);

        assertSuccessResponse($response, 200, 'OK');
        expect($response->json('data.roles'))->toContain('admin');
        expect($response->json('data.roles'))->toContain('user');
        expect($target->refresh()->hasRole('admin'))->toBeTrue();
        expect($target->hasRole('user'))->toBeTrue();
    })->group('module:iam');

    it('replaces the existing roles on reassignment', function (): void {
        loginAsSuperAdmin();
        $target = UserFactory::new()->user()->createOne();

        $response = $this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => ['admin'],
        ]);

        expect($response->json('data.roles'))->toBe(['admin']);
        expect($target->refresh()->hasRole('user'))->toBeFalse();
        expect($target->hasRole('admin'))->toBeTrue();
    })->group('module:iam');

    it('prevents non-super-admin actors from assigning the super-admin role', function (): void {
        loginAsAdmin();
        $target = UserFactory::new()->createOne();

        $this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => ['super-admin'],
        ])->assertForbidden();
    })->group('module:iam');

    it('prevents assigning roles to a super-admin user', function (): void {
        loginAsAdmin();
        $superAdmin = UserFactory::new()->superAdmin()->createOne();

        $this->putJson("/api/v1/users/{$superAdmin->id}/roles", [
            'roles' => ['admin'],
        ])->assertForbidden();
    })->group('module:iam');

    it('denies actors without the edit permission', function (): void {
        loginAsUserRole();
        $target = UserFactory::new()->createOne();

        $this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => ['admin'],
        ])->assertForbidden();
    })->group('module:iam');

    it('rejects an empty roles payload', function (): void {
        loginAsSuperAdmin();
        $target = UserFactory::new()->createOne();

        $this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => [],
        ])->assertUnprocessable();
    })->group('module:iam');

    it('rejects unknown role names', function (): void {
        loginAsSuperAdmin();
        $target = UserFactory::new()->createOne();

        $this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => ['ghost-role'],
        ])->assertUnprocessable();
    })->group('module:iam');

    it('rejects unauthenticated requests', function (): void {
        $target = UserFactory::new()->createOne();

        $this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => ['admin'],
        ])->assertUnauthorized();
    })->group('module:iam');
});
