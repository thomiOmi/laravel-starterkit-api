<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Factory;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\DeleteDeviceController;

covers(DeleteDeviceController::class);

describe('DELETE /api/v1/auth/devices/{device}', function (): void {
    it('revokes only the targeted device token', function (): void {
        $user = UserFactory::new()->createOne();
        $current = $user->createToken('current');
        $other = $user->createToken('other-device');
        $caller = $user->createToken('caller');

        $token = $user->createToken('caller');
        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->deleteJson("/api/v1/auth/devices/{$other->accessToken->id}");

        assertSuccessResponse($response, 200);
        expect($user->tokens()->whereKey($other->accessToken->id)->exists())->toBeFalse()
            ->and($user->tokens()->whereKey($current->accessToken->id)->exists())->toBeTrue();
    });

    it('allows revoking the current device and invalidates the session', function (): void {
        $user = UserFactory::new()->createOne();
        $token = $user->createToken('current');

        $token = $user->createToken('caller');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->deleteJson("/api/v1/auth/devices/{$token->accessToken->id}");

        assertSuccessResponse($response, 200);
        resolve(Factory::class)->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    });

    it('rejects unauthenticated requests', function (): void {
        $this->deleteJson('/api/v1/auth/devices/01AAAAAAAAAAAAAAAAAAAAAAAA')->assertUnauthorized();
    });
});
