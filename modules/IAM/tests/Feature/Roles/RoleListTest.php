<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Modules\IAM\Database\Factories\RoleFactory;
use Modules\IAM\Http\Controllers\V1\RoleListController;
use Modules\IAM\Models\Permission;

covers(RoleListController::class);

describe('GET /api/v1/roles', function () {
    it('rejects unauthenticated request', function () {
        $this->getJson('/api/v1/roles')->assertUnauthorized();
    });

    it('rejects without permission', function () {
        loginAsUser();

        $response = $this->getJson('/api/v1/roles');

        assertProblemResponse($response, 403);
    });

    it('returns paginated roles with permission', function () {
        Permission::firstOrCreate(['name' => PermissionEnum::RoleView->value, 'guard_name' => 'sanctum']);
        $viewer = loginAsUser();
        $viewer->givePermissionTo(PermissionEnum::RoleView->value);
        RoleFactory::new()->count(2)->create();

        $response = $this->getJson('/api/v1/roles');

        assertSuccessResponse($response, 200);
        assertPaginatedResponse($response);
        expect($response->json('data'))->toHaveCount(2);
    });

    it('rejects unverified user', function () {
        Permission::firstOrCreate(['name' => PermissionEnum::RoleView->value, 'guard_name' => 'sanctum']);
        $user = loginAsUnverifiedUser();
        $user->givePermissionTo(PermissionEnum::RoleView->value);

        $response = $this->getJson('/api/v1/roles');

        assertProblemResponse($response, 403);
    });
});
