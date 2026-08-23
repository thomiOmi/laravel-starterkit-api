<?php

declare(strict_types=1);

use App\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Modules\IAM\Http\Controllers\V1\ResendVerificationController;

covers(ResendVerificationController::class);

describe('POST /api/v1/auth/email/verification-notification', function () {
    it('sends the verification notification for unverified users', function () {
        Notification::fake();
        $user = loginAsUnverifiedUser();

        $response = $this->postJson('/api/v1/auth/email/verification-notification');

        assertSuccessResponse($response, 200);
        expect($response->json('detail'))->toContain(__('auth.email_verification_sent'));

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    });

    it('does not send for already verified users', function () {
        Notification::fake();
        loginAsUser();

        $response = $this->postJson('/api/v1/auth/email/verification-notification');

        assertSuccessResponse($response, 200);
        expect($response->json('detail'))->toContain(__('auth.email_verified'));

        Notification::assertNothingSent();
    });

    it('rejects unauthenticated requests', function () {
        $this->postJson('/api/v1/auth/email/verification-notification')->assertUnauthorized();
    });
});
