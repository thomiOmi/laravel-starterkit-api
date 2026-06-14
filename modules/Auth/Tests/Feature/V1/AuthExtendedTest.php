<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('Forgot Password', function () {
    it('sends password reset link for valid email', function () {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', __('passwords.sent'));
    });

    it('returns validation error for invalid email', function () {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('returns error for non-existent email', function () {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('message', __('auth.validation_failed'));
    });
});

describe('Reset Password', function () {
    it('resets password with valid token', function () {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', __('passwords.reset'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
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

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $response = $this->getJson($signedUrl);

        $response->assertSuccessful()
            ->assertJsonPath('data.verified', true);

        $this->assertNotNull($user->fresh()->email_verified_at);
    });

    it('returns success for already verified email', function () {
        $user = User::factory()->create();

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $response = $this->getJson($signedUrl);

        $response->assertSuccessful()
            ->assertJsonPath('data.verified', true);
    });

    it('rejects invalid hash', function () {
        $user = User::factory()->unverified()->create();

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1('wrong-email@example.com'),
            ],
        );

        $response = $this->getJson($signedUrl);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('rejects non-existent user', function () {
        $signedUrl = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => 'non-existent-id',
                'hash' => sha1('test@example.com'),
            ],
        );

        $response = $this->getJson($signedUrl);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('rejects expired signed URL', function () {
        $user = User::factory()->unverified()->create();

        $expiredUrl = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->subMinutes(10),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $response = $this->getJson($expiredUrl);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });
});

describe('Resend Verification Notification', function () {
    it('sends verification notification for unverified user', function () {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/email/verification-notification');

        $response->assertSuccessful()
            ->assertJsonPath('message', __('auth.verification_link_sent'));
    });

    it('returns success for already verified user', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/email/verification-notification');

        $response->assertSuccessful()
            ->assertJsonPath('message', __('auth.verified'));
    });

    it('requires authentication', function () {
        $response = $this->postJson('/api/v1/auth/email/verification-notification');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

    it('respects rate limit of 6 requests per minute', function () {
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('test')->plainTextToken;

        for ($i = 0; $i < 6; $i++) {
            $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/v1/auth/email/verification-notification');
        }

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/email/verification-notification');

        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    });
});
