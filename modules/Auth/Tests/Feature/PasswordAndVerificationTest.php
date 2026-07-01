<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Notifications\ResetPassword as ResetPasswordNotification;
use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;

describe('Password Management', function () {
    it('requests a password reset link', function () {
        Notification::fake();
        $user = User::factory()->create();

        expect($this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]))
            ->toBeSuccessResponse();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    })->group('v1');
});

describe('Email Verification Lifecycle', function () {
    it('completes the full verification lifecycle', function () {
        Notification::fake();
        // 1. Registration state
        $user = User::factory()->create(['email_verified_at' => null]);
        expect($user->hasVerifiedEmail())->toBeFalse();

        // 2. Ensure notification is sent (usually on registration, but here we test manual resend as well)
        Sanctum::actingAs($user, ['users:write']);
        $this->postJson('/api/v1/auth/email/verification-notification');

        $notifications = Notification::sent($user, VerifyEmail::class);
        expect($notifications)->toHaveCount(1);

        // 3. Deny access to protected features before verification
        Sanctum::actingAs($user);
        expect($this->getJson('/api/v1/users')) // Main feature route
            ->toBeProblemResponse(status: 403);

        // 4. Click verification link (Signed URL)
        $url = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        Sanctum::actingAs($user);
        expect($this->getJson($url))
            ->toBeSuccessResponse();

        $user = $user->fresh();
        expect($user->hasVerifiedEmail())->toBeTrue();

        // 5. Access main feature successfully after verification
        // Clear actingAs state so real token auth works
        Auth::guard('sanctum')->forgetUser();
        $newToken = $user->createToken('current');
        expect($this->withToken($newToken->plainTextToken)
            ->getJson('/api/v1/auth/me'))
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
        Sanctum::actingAs($userB);
        expect($this->getJson($url))
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

        Sanctum::actingAs($user);
        expect($this->getJson($url))
            ->toBeProblemResponse(status: 403);

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    })->group('v1');
});
