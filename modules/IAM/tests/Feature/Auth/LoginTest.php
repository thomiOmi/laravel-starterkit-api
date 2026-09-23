<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\LoginController;

covers(LoginController::class);

describe('POST /api/v1/auth/login', function (): void {
    it('authenticates with valid credentials', function (): void {
        UserFactory::new()->createOne(['email' => 'jane@example.com', 'password' => 'password123']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
            'device_name' => 'iPhone',
        ]);

        assertSuccessResponse($response, 200);
        expect($response->json('data.user.email'))->toBe('jane@example.com')
            ->and($response->json('data.access_token'))->not->toBeEmpty()
            ->and($response->json('data.token_type'))->toBe('Bearer');
    });

    it('rejects unknown email', function (): void {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ghost@example.com',
            'password' => 'password123',
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['email', 'password']);
    });

    it('rejects wrong password', function (): void {
        UserFactory::new()->createOne(['email' => 'jane@example.com', 'password' => 'correct']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong',
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['email', 'password']);
    });

    it('blocks banned user', function (): void {
        UserFactory::new()->banned()->createOne(['email' => 'banned@example.com', 'password' => 'password123']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'banned@example.com',
            'password' => 'password123',
        ]);

        assertProblemResponse($response, 422);
        expect($response->json('detail'))->toContain('banned');
    });
});
