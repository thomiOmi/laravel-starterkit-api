<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\UserListController;
use Modules\IAM\Models\Permission;

covers(UserListController::class);

describe('GET /api/v1/users', function () {
    it('rejects unauthenticated request', function () {
        $this->getJson('/api/v1/users')->assertUnauthorized();
    });

    it('rejects without permission', function () {
        loginAsUser();

        $response = $this->getJson('/api/v1/users');

        assertProblemResponse($response, 403);
    });

    it('returns paginated users with permission', function () {
        Permission::firstOrCreate(['name' => PermissionEnum::UserView->value, 'guard_name' => 'sanctum']);
        $viewer = loginAsUser(UserFactory::new()->createOne(['name' => 'Viewer Zero']));
        $viewer->givePermissionTo(PermissionEnum::UserView->value);
        UserFactory::new()->count(2)->create();

        $response = $this->getJson('/api/v1/users');

        assertSuccessResponse($response, 200);
        assertPaginatedResponse($response);
        expect($response->json('data'))->toHaveCount(3); // 2 + viewer
    });

    it('filters by search', function () {
        Permission::firstOrCreate(['name' => PermissionEnum::UserView->value, 'guard_name' => 'sanctum']);
        $viewer = loginAsUser(UserFactory::new()->createOne(['name' => 'Viewer Zero']));
        $viewer->givePermissionTo(PermissionEnum::UserView->value);
        UserFactory::new()->createOne(['name' => 'Alice Wonderland']);
        UserFactory::new()->createOne(['name' => 'Bob Builder']);

        $response = $this->getJson('/api/v1/users?search=Alice');

        assertSuccessResponse($response, 200);
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Alice Wonderland');
    });
});
