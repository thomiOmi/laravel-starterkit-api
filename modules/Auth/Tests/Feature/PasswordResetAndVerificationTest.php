<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Notifications\ResetPassword as ResetPasswordNotification;
use App\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('Forgot Password', function () {
    it('sends password reset link for valid email', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ])
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('detail', __('passwords.sent'))
                ->etc()
            );

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    });

    it('returns validation error for invalid email', function () {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('returns success for non-existent email to prevent enumeration', function () {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('detail', __('passwords.sent'))
                ->etc()
            );
    });
});

describe('Reset Password', function () {
    it('resets password with valid token', function () {
        Event::fake([PasswordReset::class]);

        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('detail', __('passwords.reset'))
                ->etc()
            );

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

        Event::assertDispatched(PasswordReset::class, fn ($event) => $event->user->is($user));
    });

    it('returns error for invalid token', function () {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('returns validation error for mismatched passwords', function () {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('returns error for non-existent email', function () {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'nonexistent@example.com',
            'token' => 'some-token',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });
});

describe('Email Verification', function () {
    it('verifies email with valid signed URL and hash', function () {
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->getJson($signedUrl)
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.verified', true)
                ->etc()
            );

        $this->assertNotNull($user->fresh()->email_verified_at);
    });

    it('returns success for already verified email', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->getJson($signedUrl)
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.verified', true)
                ->etc()
            );
    });

    it('rejects invalid hash', function () {
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1('wrong-email@example.com'),
            ],
        );

        $this->getJson($signedUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('rejects another user\'s verification link', function () {
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();
        Sanctum::actingAs($other);

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->getJson($signedUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('rejects expired signed URL', function () {
        $this->travelTo(now()->startOfDay());

        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $expiredUrl = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->subMinutes(10),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->getJson($expiredUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('rejects unauthenticated request', function () {
        $user = User::factory()->unverified()->create();

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $response = $this->getJson($signedUrl);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });
});

describe('Resend Verification Notification', function () {
    it('sends verification notification for unverified user', function () {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user, ['users:write']);

        $this->postJson('/api/v1/auth/email/verification-notification')
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('detail', __('auth.verification_link_sent'))
                ->etc()
            );

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    });

    it('returns success for already verified user', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['users:write']);

        $this->postJson('/api/v1/auth/email/verification-notification')
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('detail', __('auth.verified'))
                ->etc()
            );
    });

    it('requires authentication', function () {
        $response = $this->postJson('/api/v1/auth/email/verification-notification');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

});
