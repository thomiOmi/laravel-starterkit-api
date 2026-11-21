<?php

declare(strict_types=1);

use Modules\IAM\Http\Controllers\V1\MeController;
use Modules\IAM\Http\Controllers\V1\UpdateProfileController;

covers(MeController::class);
covers(UpdateProfileController::class);

describe('GET /api/v1/auth/me', function (): void {
    it('returns authenticated user', function (): void {
        $user = loginAsUser();

        $response = $this->getJson('/api/v1/auth/me');

        assertSuccessResponse($response, 200);
        expect($response->json('data.email'))->toBe($user->email);
    });

    it('rejects unauthenticated request', function (): void {
        $response = $this->getJson('/api/v1/auth/me');

        assertProblemResponse($response, 401);
    });
});

describe('PUT /api/v1/auth/me', function (): void {
    it('updates profile name', function (): void {
        loginAsUser();

        $response = $this->putJson('/api/v1/auth/me', [
            'name' => 'New Name',
        ]);

        assertSuccessResponse($response, 200);
        expect($response->json('data.user.name'))->toBe('New Name');
    });

    it('updates the email with an ASCII address', function (): void {
        loginAsUser();

        $response = $this->putJson('/api/v1/auth/me', [
            'email' => 'renamed@example.com',
        ]);

        assertSuccessResponse($response, 200);
        expect($response->json('data.user.email'))->toBe('renamed@example.com');
    });

    it('rejects non-ASCII emails', function (string $email): void {
        $user = loginAsUser();

        $response = $this->putJson('/api/v1/auth/me', [
            'email' => $email,
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['email']);
        expect($user->refresh()->email)->not->toBe($email);
    })->with([
        'accented latin' => ["jan\u{e9}@example.com"],
        'cyrillic lookalike' => ["\u{0430}dmin@example.com"],
    ]);

    it('rejects unverified user', function (): void {
        loginAsUnverifiedUser();

        $response = $this->putJson('/api/v1/auth/me', ['name' => 'New Name']);

        assertProblemResponse($response, 403);
    });
});
