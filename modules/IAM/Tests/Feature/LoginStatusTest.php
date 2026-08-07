<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;
use Modules\IAM\Database\Factories\UserFactory;

dataset('blocked login statuses', [
    'banned' => [UserStatusEnum::Banned, 'auth.account_banned'],
    'suspended' => [UserStatusEnum::Suspended, 'auth.account_suspended'],
    'inactive' => [UserStatusEnum::Inactive, 'auth.account_inactive'],
]);

describe('login account status enforcement', function (): void {
    beforeEach(function (): void {
        config()->set('rate-limiting.auth.limit_per_email', 100);
        config()->set('rate-limiting.auth.limit_per_ip', 100);
    });

    it('blocks login for blocked account statuses', function (UserStatusEnum $status, string $messageKey): void {
        $user = UserFactory::new()->createOne([
            'email' => 'blocked-'.$status->value.'@example.com',
            'password' => 'secret-password',
        ]);
        $user->update(['status' => $status]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        assertProblemResponse($response, 422, 'validation');
        expect($response->json('errors.email.0'))->toBe(__($messageKey));
    })->with('blocked login statuses')->group('module:iam');

    it('allows login for active users', function (): void {
        $user = UserFactory::new()->createOne([
            'email' => 'active@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        assertSuccessResponse($response, 200, 'OK');
    })->group('module:iam');

    it('allows login for pending users awaiting email verification', function (): void {
        $user = UserFactory::new()->pending()->createOne([
            'email' => 'pending@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        assertSuccessResponse($response, 200, 'OK');
    })->group('module:iam');
});
