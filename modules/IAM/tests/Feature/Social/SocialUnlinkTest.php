<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\SocialUnlinkController;

covers(SocialUnlinkController::class);

describe('DELETE /api/v1/auth/social/{provider}', function () {
    it('unlinks a provider while another sign-in method remains', function () {
        $user = UserFactory::new()->createOne(['password' => 'my-password']);
        $user->socialAccounts()->createMany([
            ['provider' => 'google', 'provider_id' => 'g-1'],
            ['provider' => 'github', 'provider_id' => 'gh-1'],
        ]);
        loginAsUser($user);

        $response = $this->deleteJson('/api/v1/auth/social/google');

        assertSuccessResponse($response, 200);
        expect($user->socialAccounts()->where('provider', 'google')->exists())->toBeFalse()
            ->and($user->socialAccounts()->count())->toBe(1);
    });

    it('blocks unlinking the last method when the user has no password', function () {
        $user = UserFactory::new()->social('google')->createOne();
        loginAsUser($user);

        $response = $this->deleteJson('/api/v1/auth/social/google');

        assertProblemResponse($response, 400);
        expect($response->json('detail'))->toContain(__('auth.social_unlink_blocked'))
            ->and($user->socialAccounts()->count())->toBe(1);
    });

    it('allows unlinking the last provider when a password is set', function () {
        $user = UserFactory::new()->social('google')->createOne(['password' => 'fallback-pass']);
        loginAsUser($user);

        assertSuccessResponse($this->deleteJson('/api/v1/auth/social/google'), 200);
        expect($user->socialAccounts()->count())->toBe(0);
    });

    it('rejects unlinking a provider that is not linked', function () {
        $user = UserFactory::new()->createOne(['password' => 'my-password']);
        loginAsUser($user);

        assertProblemResponse($this->deleteJson('/api/v1/auth/social/github'), 400);
    });

    it('rejects unauthenticated requests', function () {
        $this->deleteJson('/api/v1/auth/social/google')->assertUnauthorized();
    });
});
