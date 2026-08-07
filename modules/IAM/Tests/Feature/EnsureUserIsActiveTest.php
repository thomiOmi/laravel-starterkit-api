<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;
use Illuminate\Support\Facades\Route;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Middleware\EnsureUserIsActive;

covers(EnsureUserIsActive::class);

dataset('blocked middleware statuses', [
    'banned' => [UserStatusEnum::Banned, 'auth.account_banned'],
    'suspended' => [UserStatusEnum::Suspended, 'auth.account_suspended'],
    'inactive' => [UserStatusEnum::Inactive, 'auth.account_inactive'],
]);

describe('EnsureUserIsActive', function (): void {
    beforeEach(function (): void {
        Route::middleware(['api', 'auth:sanctum', 'active'])
            ->get('/__test/active-only', fn (): array => ['ok' => true]);
    });

    it('returns unauthenticated problem response when no user is authenticated', function (): void {
        $response = $this->getJson('/__test/active-only');

        assertProblemResponse($response, 401, 'authentication-required');
    })->group('module:iam');

    it('passes the request through when the user is active', function (): void {
        loginAsUser();

        $response = $this->getJson('/__test/active-only');

        $response->assertOk()->assertJson(['ok' => true]);
    })->group('module:iam');

    it('passes the request through when the user is pending email verification', function (): void {
        loginAsUser(UserFactory::new()->pending()->createOne());

        $response = $this->getJson('/__test/active-only');

        $response->assertOk()->assertJson(['ok' => true]);
    })->group('module:iam');

    it('returns forbidden problem response for blocked account statuses', function (UserStatusEnum $status, string $messageKey): void {
        loginAsUser(UserFactory::new()->createOne(['status' => $status]));

        $response = $this->getJson('/__test/active-only');

        assertProblemResponse($response, 403, 'access-denied');
        expect($response->json('detail'))->toBe(__($messageKey));
    })->with('blocked middleware statuses')->group('module:iam');
});
