<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;

describe('native rate limit headers on real throttled routes', function (): void {
    describe('authenticated limiter (one Limit)', function (): void {
        beforeEach(function (): void {
            config()->set('rate-limiting.authenticated.limit', 1);
        });

        it('sends limit and remaining headers on a successful response', function (): void {
            loginAsUser();

            $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
                ->getJson('/api/v1/auth/me');

            assertSuccessResponse($response, 200);
            $response->assertHeader('X-RateLimit-Limit', '1')
                ->assertHeader('X-RateLimit-Remaining', '0');
        });

        it('sends retry-after and limit headers on the 429 problem response', function (): void {
            loginAsUser();

            $client = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

            $client->getJson('/api/v1/auth/me')->assertOk();

            $response = $client->getJson('/api/v1/auth/me');

            assertProblemResponse($response, 429, 'rate-limit-exceeded');
            $response->assertHeader('X-RateLimit-Limit', '1')
                ->assertHeader('X-RateLimit-Remaining', '0')
                ->assertHeader('X-RateLimit-Reset');

            expect((int) $response->headers->get('Retry-After'))->toBeGreaterThan(0)
                ->toBeLessThanOrEqual(60)
                ->and((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(0);
        });
    });

    describe('auth limiter (two Limits: per-email then per-IP)', function (): void {
        beforeEach(function (): void {
            config()->set('rate-limiting.auth.limit_per_email', 1);
            config()->set('rate-limiting.auth.limit_per_ip', 100);

            UserFactory::new()->createOne([
                'email' => 'user@example.com',
                'password' => 'password123',
            ]);
        });

        it('sends limit headers on a successful response from the first limit', function (): void {
            // ThrottleRequests::getHeaders skips later Limits while the response
            // already declares a lower or equal X-RateLimit-Remaining, so the
            // per-email Limit (1) wins over the per-IP Limit (100).
            $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
                ->postJson('/api/v1/auth/login', [
                    'email' => 'user@example.com',
                    'password' => 'password123',
                ]);

            assertSuccessResponse($response, 200);
            $response->assertHeader('X-RateLimit-Limit', '1')
                ->assertHeader('X-RateLimit-Remaining', '0');
        });

        it('sends retry-after and limit headers on the 429 problem response', function (): void {
            $client = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

            $payload = [
                'email' => 'user@example.com',
                'password' => 'password123',
            ];

            $client->postJson('/api/v1/auth/login', $payload)->assertOk();

            $response = $client->postJson('/api/v1/auth/login', $payload);

            // buildException uses the first Limit that trips (per-email), so
            // the 429 headers carry that Limit's budget.
            assertProblemResponse($response, 429, 'rate-limit-exceeded');
            $response->assertHeader('X-RateLimit-Limit', '1')
                ->assertHeader('X-RateLimit-Remaining', '0')
                ->assertHeader('X-RateLimit-Reset');

            expect((int) $response->headers->get('Retry-After'))->toBeGreaterThan(0)
                ->toBeLessThanOrEqual(60)
                ->and((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(0);
        });
    });
});
