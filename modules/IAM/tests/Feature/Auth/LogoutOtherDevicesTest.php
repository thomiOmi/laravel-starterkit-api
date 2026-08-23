<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\LogoutOtherDevicesController;

covers(LogoutOtherDevicesController::class);

describe('POST /api/v1/auth/devices/logout-others', function () {
    it('revokes every other session and keeps the current one', function () {
        $user = UserFactory::new()->createOne(['password' => 'my-password']);
        $current = $user->createToken('current');
        $user->createToken('old-phone');
        $user->createToken('old-tablet');

        $response = $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
            ->postJson('/api/v1/auth/devices/logout-others', ['current_password' => 'my-password']);

        assertSuccessResponse($response, 200);
        expect($user->tokens()->count())->toBe(1)
            ->and($user->tokens()->first()?->name)->toBe('current');
    });

    it('rejects a wrong password', function () {
        $user = UserFactory::new()->createOne(['password' => 'my-password']);
        $token = $user->createToken('current');
        $user->createToken('other');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/devices/logout-others', ['current_password' => 'wrong']);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['current_password']);
        expect($user->tokens()->count())->toBe(2);
    });
});
