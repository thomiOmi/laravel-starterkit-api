<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\DeleteAccountController;

covers(DeleteAccountController::class);

describe('DELETE /api/v1/auth/account', function () {
    it('soft-deletes the account and revokes all tokens', function () {
        $user = UserFactory::new()->createOne(['password' => 'my-password']);
        $token = $user->createToken('current');
        $user->createToken('second-device');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->deleteJson('/api/v1/auth/account', ['password' => 'my-password']);

        assertSuccessResponse($response, 200);
        expect($user->fresh()?->deleted_at)->not->toBeNull()
            ->and($user->tokens()->count())->toBe(0);
    });

    it('rejects a wrong password', function () {
        $user = UserFactory::new()->createOne(['password' => 'my-password']);
        $token = $user->createToken('current');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->deleteJson('/api/v1/auth/account', ['password' => 'wrong-password']);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['password']);
        expect($user->fresh()?->deleted_at)->toBeNull();
    });

    it('rejects unauthenticated requests', function () {
        $this->deleteJson('/api/v1/auth/account')->assertUnauthorized();
    });
});
