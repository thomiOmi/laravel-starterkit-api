<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Factory;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\LogoutController;

covers(LogoutController::class);

describe('POST /api/v1/auth/logout', function (): void {
    it('revokes the current token', function (): void {
        $user = UserFactory::new()->createOne();
        $token = $user->createToken('device');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/logout');

        assertSuccessResponse($response, 200);
        expect($user->tokens()->count())->toBe(0);

        resolve(Factory::class)->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    });

    it('rejects unauthenticated requests', function (): void {
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    });
});
