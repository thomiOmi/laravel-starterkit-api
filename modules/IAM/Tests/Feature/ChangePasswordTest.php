<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Modules\IAM\Database\Factories\UserFactory;

describe('change password', function (): void {
    it('updates the password when the current password is correct', function (): void {
        $user = loginAsUser(UserFactory::new()->createOne(['password' => 'secret-password']));

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'secret-password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        assertSuccessResponse($response, 200, __('auth.password_updated'));

        $user->refresh();

        expect(Hash::check('new-secret-password', (string) $user->password))->toBeTrue();
    })->group('module:iam');

    it('revokes other device tokens after a password change', function (): void {
        $user = loginAsUser(UserFactory::new()->createOne(['password' => 'secret-password']));
        $otherToken = $user->createToken('other-device');

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'secret-password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertOk();

        expect($user->tokens()->where('id', $otherToken->accessToken->getKey())->exists())->toBeFalse();
    })->group('module:iam');

    it('returns 422 when the current password is incorrect', function (): void {
        loginAsUser(UserFactory::new()->createOne(['password' => 'secret-password']));

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'wrong-password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        assertProblemResponse($response, 422, 'validation');
        expect($response->json('errors.current_password.0'))->toBe(__('auth.password_invalid'));
    })->group('module:iam');

    it('returns 422 when the new password is not confirmed', function (): void {
        loginAsUser(UserFactory::new()->createOne(['password' => 'secret-password']));

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'secret-password',
            'password' => 'new-secret-password',
        ]);

        assertProblemResponse($response, 422, 'validation');
    })->group('module:iam');

    it('returns 401 when unauthenticated', function (): void {
        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'secret-password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertUnauthorized();
    })->group('module:iam');
});
