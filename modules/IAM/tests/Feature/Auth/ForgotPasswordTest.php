<?php

declare(strict_types=1);

use App\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\ForgotPasswordController;

covers(ForgotPasswordController::class);

describe('POST /api/v1/auth/forgot-password', function () {
    it('sends a reset link for a known email', function () {
        Notification::fake();
        $user = UserFactory::new()->createOne(['email' => 'jane@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'jane@example.com']);

        assertSuccessResponse($response, 200);
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    });

    it('responds identically for unknown emails to prevent enumeration', function () {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ghost@example.com']);

        assertSuccessResponse($response, 200);
        Notification::assertNothingSent();
    });

    it('validates the email format', function () {
        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-email']);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['email']);
    });
});
