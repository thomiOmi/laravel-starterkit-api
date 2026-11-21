<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Modules\IAM\Database\Factories\UserFactory;

covers(EnsureUserIsActive::class);

describe('active middleware', function (): void {
    beforeEach(function (): void {
        Route::middleware(['api', 'auth:sanctum', 'active'])->get('/_test/active-only', fn (): JsonResponse => response()->json(['ok' => true]));
    });

    it('blocks unauthenticated requests', function (): void {
        assertProblemResponse($this->getJson('/_test/active-only'), 401, 'authentication-required');
    });

    it('allows active users', function (): void {
        loginAsUser();

        $this->getJson('/_test/active-only')->assertOk();
    });

    it('allows pending users to authenticate', function (): void {
        loginAsUser(UserFactory::new()->pending()->createOne(['email_verified_at' => now()]));

        $this->getJson('/_test/active-only')->assertOk();
    });

    it('blocks :dataset users with the matching message', function (UserFactory $factory, string $messageKey): void {
        $user = $factory->createOne(['email_verified_at' => now()]);
        loginAsUser($user);

        $response = $this->getJson('/_test/active-only');

        assertProblemResponse($response, 403);
        expect($response->json('detail'))->toContain(__($messageKey));
    })->with([
        'banned' => [UserFactory::new()->banned(), 'auth.account_banned'],
        'suspended' => [UserFactory::new()->suspended(), 'auth.account_suspended'],
        'inactive' => [UserFactory::new()->inactive(), 'auth.account_inactive'],
    ]);
});
