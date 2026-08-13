<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\User;

describe('delete account', function (): void {
    it('soft-deletes the account and revokes all tokens with the correct password', function (): void {
        $user = loginAsUser(UserFactory::new()->createOne(['password' => 'secret-password']));
        $token = $user->createToken('test-device');

        $response = $this->deleteJson('/api/v1/auth/account', [
            'password' => 'secret-password',
        ]);

        assertSuccessResponse($response, 200, __('general.resource_deleted', ['resource' => 'Account']));

        $user->refresh();

        expect($user->trashed())->toBeTrue();
        expect($user->tokens()->exists())->toBeFalse();
        expect($token->accessToken->exists())->toBeFalse();
    })->group('module:iam');

    it('keeps the account when the password is incorrect', function (): void {
        $user = loginAsUser(UserFactory::new()->createOne(['password' => 'secret-password']));

        $response = $this->deleteJson('/api/v1/auth/account', [
            'password' => 'wrong-password',
        ]);

        assertProblemResponse($response, 422, 'validation');
        expect($response->json('errors.password.0'))->toBe(__('auth.password_invalid'));

        $user->refresh();

        expect($user->trashed())->toBeFalse();
    })->group('module:iam');

    it('returns 401 when unauthenticated', function (): void {
        $this->deleteJson('/api/v1/auth/account', [
            'password' => 'secret-password',
        ])->assertUnauthorized();
    })->group('module:iam');

    it('rolls back the token revocation when the account delete fails', function (): void {
        $user = loginAsUser(UserFactory::new()->createOne(['password' => 'secret-password']));
        $token = $user->createToken('test-device');

        User::deleting(fn (): never => throw new RuntimeException('forced delete failure'));

        $this->deleteJson('/api/v1/auth/account', [
            'password' => 'secret-password',
        ])->assertServerError();

        $user->refresh();

        expect($user->trashed())->toBeFalse();
        expect($user->tokens()->exists())->toBeTrue();
        expect($token->accessToken->exists())->toBeTrue();
    })->group('module:iam');
});
