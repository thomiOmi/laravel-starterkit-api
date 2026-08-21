<?php

declare(strict_types=1);

use Modules\IAM\Http\Controllers\V1\MeController;
use Modules\IAM\Http\Controllers\V1\UpdateProfileController;

covers(MeController::class);
covers(UpdateProfileController::class);

describe('GET /api/v1/auth/me', function () {
    it('returns authenticated user', function () {
        $user = loginAsUser();

        $response = $this->getJson('/api/v1/auth/me');

        assertSuccessResponse($response, 200);
        expect($response->json('data.email'))->toBe($user->email);
    });

    it('rejects unauthenticated request', function () {
        $response = $this->getJson('/api/v1/auth/me');

        assertProblemResponse($response, 401);
    });
});

describe('PUT /api/v1/auth/me', function () {
    it('updates profile name', function () {
        loginAsUser();

        $response = $this->putJson('/api/v1/auth/me', [
            'name' => 'New Name',
        ]);

        assertSuccessResponse($response, 200);
        expect($response->json('data.user.name'))->toBe('New Name');
    });

    it('rejects unverified user', function () {
        loginAsUnverifiedUser();

        $response = $this->putJson('/api/v1/auth/me', ['name' => 'New Name']);

        assertProblemResponse($response, 403);
    });
});
