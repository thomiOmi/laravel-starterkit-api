<?php

declare(strict_types=1);
use Modules\IAM\Database\Factories\UserFactory;

describe('login rate limit', function (): void {
    describe('per-email', function (): void {
        it('returns 429 with rate-limit headers on the third identical login attempt', function (): void {
            config()->set('rate-limiting.auth.limit_per_email', 2);
            config()->set('rate-limiting.auth.limit_per_ip', 100);

            $payload = ['email' => 'limit@example.com', 'password' => 'wrong'];

            $this->postJson('/api/v1/auth/login', $payload)
                ->assertStatus(422)
                ->assertHeader('X-RateLimit-Limit', '2');

            $this->postJson('/api/v1/auth/login', $payload)
                ->assertStatus(422)
                ->assertHeader('X-RateLimit-Limit', '2');

            $response = $this->postJson('/api/v1/auth/login', $payload);

            assertProblemResponse($response, 429, 'rate-limit-exceeded');

            expect($response->json('detail'))->toBe('Too Many Attempts.');

            $response->assertHeader('X-RateLimit-Limit', '2');
            $response->assertHeader('X-RateLimit-Remaining', '0');
            expect((int) $response->headers->get('Retry-After'))->toBeGreaterThanOrEqual(1);
            expect((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(time());
        })->group('smoke');
    });

    describe('login success flow', function (): void {
        it('returns the success envelope with rate-limit headers for valid credentials', function (): void {
            UserFactory::new()
                ->createOne(['email' => 'success@example.com', 'password' => 'secret-password']);

            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'success@example.com',
                'password' => 'secret-password',
            ]);

            assertSuccessResponse($response, 200, 'OK');

            expect($response->json('data'))
                ->toHaveKeys(['user', 'access_token', 'token_type', 'expires_at', 'expires_in']);

            $response->assertHeader('X-RateLimit-Limit', (string) config()->integer('rate-limiting.auth.limit_per_email'));
            $response->assertHeader('X-RateLimit-Remaining', '4');
        })->group('smoke');
    });
});
