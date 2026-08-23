<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Password;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\ResetPasswordController;

covers(ResetPasswordController::class);

describe('POST /api/v1/auth/reset-password', function () {
    it('resets the password with a valid token', function () {
        $user = UserFactory::new()->createOne(['email' => 'jane@example.com', 'password' => 'old-password']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        assertSuccessResponse($response, 200);
        expect($user->fresh()?->password)->not->toBe($user->password);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'new-password123',
        ])->assertOk();
    });

    it('rejects an invalid token', function () {
        $user = UserFactory::new()->createOne(['email' => 'jane@example.com']);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'jane@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['email']);
    });
});
