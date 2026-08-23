<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\ChangePasswordController;

covers(ChangePasswordController::class);

describe('POST /api/v1/auth/change-password', function () {
    it('changes the password and revokes other sessions', function () {
        $user = UserFactory::new()->createOne(['password' => 'current-pass']);
        $current = $user->createToken('current');
        $user->createToken('other-device');

        $response = $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'current-pass',
                'password' => 'brand-new-pass',
                'password_confirmation' => 'brand-new-pass',
            ]);

        assertSuccessResponse($response, 200);
        expect($user->tokens()->count())->toBe(1);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'brand-new-pass',
        ])->assertOk();
    });

    it('rejects a wrong current password', function () {
        $user = UserFactory::new()->createOne(['password' => 'current-pass']);
        $token = $user->createToken('current');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'wrong-pass',
                'password' => 'brand-new-pass',
                'password_confirmation' => 'brand-new-pass',
            ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['current_password']);
    });

    it('rejects unauthenticated requests', function () {
        $this->postJson('/api/v1/auth/change-password')->assertUnauthorized();
    });
});
