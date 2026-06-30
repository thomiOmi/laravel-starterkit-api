<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Modules\User\Models\User;

describe('Password Management', function () {
    it('requests a password reset link', function () {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->toBeSuccessResponse();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    })->group('v1');
});

describe('Email Verification Lifecycle (SOP)', function () {
    it('completes the full verification lifecycle', function () {
        Notification::fake();
        // 1. Registration state
        $user = User::factory()->create(['email_verified_at' => null]);
        expect($user->hasVerifiedEmail())->toBeFalse();

        // 2. Ensure notification is sent (usually on registration, but here we test manual resend as well)
        $this->actingAs($user, ['users:write'])
            ->postJson('/api/v1/auth/email/verification-notification')
            ->toBeSuccessResponse();

        Notification::assertSentTo($user, VerifyEmail::class, function ($notification, $channels) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_contains($url, "/api/v1/auth/email/verify/{$user->id}");
        });

        // 3. Deny access to protected features before verification
        $this->actingAs($user)
            ->getJson('/api/v1/users') // Main feature route
            ->toBeProblemResponse(status: 403);

        // 4. Click verification link (Signed URL)
        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->actingAs($user)
            ->getJson($url)
            ->toBeSuccessResponse();

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

        // 5. Access main feature successfully after verification
        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->toBeSuccessResponse();
    })->group('v1');

    it('denies verification if User ID is manipulated in the URL', function () {
        $userA = User::factory()->create(['email_verified_at' => null]);
        $userB = User::factory()->create(['email_verified_at' => null]);

        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $userA->id, 'hash' => sha1($userA->getEmailForVerification())]
        );

        // User B tries to use User A verification link
        $this->actingAs($userB)
            ->getJson($url)
            ->toBeProblemResponse(status: 403);

        expect($userA->fresh()->hasVerifiedEmail())->toBeFalse();
    })->group('v1');

    it('denies verification if the signed link is expired', function () {
        $user = User::factory()->create(['email_verified_at' => null]);

        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->subMinutes(1), // Already expired
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->actingAs($user)
            ->getJson($url)
            ->toBeProblemResponse(status: 403);

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    })->group('v1');
});
