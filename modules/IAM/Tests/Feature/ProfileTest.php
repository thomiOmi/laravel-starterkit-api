<?php

declare(strict_types=1);

use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\Media\Database\Factories\MediaFactory;

beforeEach(function (): void {
    $this->seed(IAMSeeder::class);
    Storage::fake('public');
});

describe('PROF-01 update profile', function (): void {
    it('updates the name without requiring verification', function (): void {
        loginAsUser();

        $response = $this->putJson('/api/v1/auth/me', ['name' => 'New Name']);

        assertSuccessResponse($response, 200, 'OK');
        expect($response->json('data.user.name'))->toBe('New Name');
        expect($response->json('data.verification_required'))->toBeFalse();
    })->group('module:iam');

    it('sets the avatar from a media owned by the user on the public disk', function (): void {
        $user = loginAsUser();
        $media = MediaFactory::new()->createOne([
            'disk' => 'public',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->putJson('/api/v1/auth/me', ['avatar' => $media->id]);

        assertSuccessResponse($response, 200, 'OK');
        expect($response->json('data.user.avatar'))->toBe(Storage::disk('public')->url($media->path));
    })->group('module:iam');

    it('rejects an avatar media owned by another user', function (): void {
        loginAsUser();
        $foreignMedia = MediaFactory::new()->createOne(['disk' => 'public']);

        $response = $this->putJson('/api/v1/auth/me', ['avatar' => $foreignMedia->id]);

        assertProblemResponse($response, 400, 'invalid-request-payload');
        expect($response->json('detail'))->toBe(__('validation.avatar_invalid'));
    })->group('module:iam');

    it('rejects an avatar media not stored on the public disk', function (): void {
        $user = loginAsUser();
        $privateMedia = MediaFactory::new()->createOne([
            'disk' => 'local',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->putJson('/api/v1/auth/me', ['avatar' => $privateMedia->id]);

        assertProblemResponse($response, 400, 'invalid-request-payload');
        expect($response->json('detail'))->toBe(__('validation.avatar_invalid'));
    })->group('module:iam');

    it('rejects a non-ulid avatar reference', function (): void {
        loginAsUser();

        $response = $this->putJson('/api/v1/auth/me', ['avatar' => 'not-a-ulid']);

        $response->assertUnprocessable();
        expect($response->json('errors.avatar.0'))->toBe(__('validation.ulid', ['attribute' => 'avatar']));
    })->group('module:iam');

    it('rejects a duplicate email used by another user', function (): void {
        loginAsUser();
        UserFactory::new()->createOne(['email' => 'taken@example.com', 'email_verified_at' => now()]);

        $response = $this->putJson('/api/v1/auth/me', ['email' => 'taken@example.com']);

        $response->assertUnprocessable();
        expect($response->json('errors.email.0'))->toBe(__('validation.unique', ['attribute' => 'email']));
    })->group('module:iam');

    it('returns 401 when unauthenticated', function (): void {
        $this->putJson('/api/v1/auth/me', ['name' => 'X'])->assertUnauthorized();
    })->group('module:iam');
});

describe('PROF-02 email change re-verification', function (): void {
    it('clears verification, sends a notification and flags verification as required', function (): void {
        Notification::fake();
        $user = loginAsUser();

        $response = $this->putJson('/api/v1/auth/me', ['email' => 'new@example.com']);

        assertSuccessResponse($response, 200, 'OK');
        expect($response->json('data.verification_required'))->toBeTrue();
        expect($response->json('detail'))->toBe(__('auth.email_change_verify'));

        $user->refresh();

        expect($user->email)->toBe('new@example.com');
        expect($user->email_verified_at)->toBeNull();
        Notification::assertSentTo($user, VerifyEmail::class);
    })->group('module:iam');

    it('locks verified-only endpoints until the new email is verified', function (): void {
        loginAsUser();

        $this->putJson('/api/v1/auth/me', ['email' => 'new@example.com'])->assertOk();

        $response = $this->getJson('/api/v1/auth/me')->assertOk();

        expect($response->json('data.email_verified_at'))->toBeNull();

        $this->putJson('/api/v1/auth/me', ['name' => 'New Name'])->assertForbidden();
    })->group('module:iam');

    it('restores access after verifying the new email via the signed link', function (): void {
        $user = loginAsUser();

        $this->putJson('/api/v1/auth/me', ['email' => 'new@example.com'])->assertOk();

        $url = URL::signedRoute('v1.iam.auth.verification.verify', [
            'id' => $user->id,
            'hash' => sha1('new@example.com'),
        ]);

        $response = $this->getJson($url);

        assertSuccessResponse($response, 200, 'OK');
        expect($response->json('data.verified'))->toBeTrue();

        $this->getJson('/api/v1/auth/me')->assertOk();
    })->group('module:iam');
});
