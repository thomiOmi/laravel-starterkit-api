<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\UserShowController;
use Modules\IAM\Models\Permission;

covers(UserShowController::class);

describe('GET /api/v1/users/{user}', function () {
    it('allows users to view themselves without permission', function () {
        $user = loginAsUser();

        $response = $this->getJson("/api/v1/users/{$user->id}");

        assertSuccessResponse($response, 200);
        expect($response->json('data.id'))->toBe($user->id);
    });

    it('rejects strangers without the view permission', function () {
        loginAsUser();
        $other = UserFactory::new()->createOne();

        assertProblemResponse($this->getJson("/api/v1/users/{$other->id}"), 403);
    });

    it('permits viewers with the view permission', function () {
        Permission::firstOrCreate(['name' => PermissionEnum::UserView->value, 'guard_name' => 'sanctum']);
        $viewer = loginAsUser();
        $viewer->givePermissionTo(PermissionEnum::UserView->value);
        $target = UserFactory::new()->createOne();

        assertSuccessResponse($this->getJson("/api/v1/users/{$target->id}"), 200);
    });

    it('rejects unauthenticated requests', function () {
        $this->getJson('/api/v1/users/01AAAAAAAAAAAAAAAAAAAAAAAA')->assertUnauthorized();
    });
});
