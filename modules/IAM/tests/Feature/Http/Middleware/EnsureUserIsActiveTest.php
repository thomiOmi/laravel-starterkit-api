<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Middleware\EnsureUserIsActive;

covers(EnsureUserIsActive::class);

describe('active middleware', function () {
    beforeEach(function () {
        Route::middleware(['api', 'auth:sanctum', 'active'])->get('/_test/active-only', fn (): JsonResponse => response()->json(['ok' => true]));
    });

    it('blocks unauthenticated requests', function () {
        assertProblemResponse($this->getJson('/_test/active-only'), 401, 'authentication-required');
    });

    it('allows active users', function () {
        loginAsUser();

        $this->getJson('/_test/active-only')->assertOk();
    });

    it('allows pending users to authenticate', function () {
        loginAsUser(UserFactory::new()->pending()->createOne(['email_verified_at' => now()]));

        $this->getJson('/_test/active-only')->assertOk();
    });

    it('blocks blocked statuses with the matching message key', function (string $state) {
        $factory = match ($state) {
            'banned' => UserFactory::new()->banned(),
            'suspended' => UserFactory::new()->suspended(),
            'inactive' => UserFactory::new()->inactive(),
            default => throw new InvalidArgumentException("Unknown state [{$state}]."),
        };

        $user = $factory->createOne(['email_verified_at' => now()]);
        loginAsUser($user);

        $response = $this->getJson('/_test/active-only');

        assertProblemResponse($response, 403);
        expect($response->json('detail'))->toContain(__($user->status->blockedMessageKey()));
    })->with([
        'banned',
        'suspended',
        'inactive',
    ]);
});
