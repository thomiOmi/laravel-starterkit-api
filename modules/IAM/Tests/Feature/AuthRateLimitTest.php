<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Mail;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Database\Seeders\RoleSeeder;

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

    describe('per-ip', function (): void {
        it('returns 429 with rate-limit headers on the third distinct-email login from the same IP', function (): void {
            config()->set('rate-limiting.auth.limit_per_email', 100);
            config()->set('rate-limiting.auth.limit_per_ip', 2);

            $this->postJson('/api/v1/auth/login', ['email' => 'ip1@example.com', 'password' => 'wrong'])
                ->assertStatus(422)
                ->assertHeader('X-RateLimit-Limit', '2');

            $this->postJson('/api/v1/auth/login', ['email' => 'ip2@example.com', 'password' => 'wrong'])
                ->assertStatus(422)
                ->assertHeader('X-RateLimit-Limit', '2');

            $response = $this->postJson('/api/v1/auth/login', ['email' => 'ip3@example.com', 'password' => 'wrong']);

            assertProblemResponse($response, 429, 'rate-limit-exceeded');

            expect($response->json('detail'))->toBe('Too Many Attempts.');

            $response->assertHeader('X-RateLimit-Limit', '2');
            $response->assertHeader('X-RateLimit-Remaining', '0');
            expect((int) $response->headers->get('Retry-After'))->toBeGreaterThanOrEqual(1);
            expect((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(time());
        })->group('smoke');
    });
});

describe('register rate limit', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    describe('per-email', function (): void {
        it('returns 429 with rate-limit headers on the third identical registration attempt', function (): void {
            config()->set('rate-limiting.auth.limit_per_email', 2);
            config()->set('rate-limiting.auth.limit_per_ip', 100);

            $payload = [
                'name' => 'Rate Limit User',
                'email' => 'reg@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ];

            $this->postJson('/api/v1/auth/register', $payload)
                ->assertCreated()
                ->assertHeader('X-RateLimit-Limit', '2');

            $this->postJson('/api/v1/auth/register', $payload)
                ->assertStatus(422)
                ->assertHeader('X-RateLimit-Limit', '2');

            $response = $this->postJson('/api/v1/auth/register', $payload);

            assertProblemResponse($response, 429, 'rate-limit-exceeded');

            expect($response->json('detail'))->toBe('Too Many Attempts.');

            $response->assertHeader('X-RateLimit-Limit', '2');
            $response->assertHeader('X-RateLimit-Remaining', '0');
            expect((int) $response->headers->get('Retry-After'))->toBeGreaterThanOrEqual(1);
            expect((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(time());
        })->group('smoke');
    });

    describe('per-ip', function (): void {
        it('returns 429 with rate-limit headers on the third distinct-email registration from the same IP', function (): void {
            config()->set('rate-limiting.auth.limit_per_email', 100);
            config()->set('rate-limiting.auth.limit_per_ip', 2);

            $this->postJson('/api/v1/auth/register', [
                'name' => 'Ip One',
                'email' => 'regip1@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
                ->assertCreated()
                ->assertHeader('X-RateLimit-Limit', '2');

            $this->postJson('/api/v1/auth/register', [
                'name' => 'Ip Two',
                'email' => 'regip2@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
                ->assertCreated()
                ->assertHeader('X-RateLimit-Limit', '2');

            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'Ip Three',
                'email' => 'regip3@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            assertProblemResponse($response, 429, 'rate-limit-exceeded');

            expect($response->json('detail'))->toBe('Too Many Attempts.');

            $response->assertHeader('X-RateLimit-Limit', '2');
            $response->assertHeader('X-RateLimit-Remaining', '0');
            expect((int) $response->headers->get('Retry-After'))->toBeGreaterThanOrEqual(1);
            expect((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(time());
        })->group('smoke');
    });
});

describe('forgot-password rate limit', function (): void {
    beforeEach(function (): void {
        Mail::fake();
    });

    describe('per-email', function (): void {
        it('returns 429 with rate-limit headers on the third identical password reset request', function (): void {
            config()->set('rate-limiting.auth.limit_per_email', 2);
            config()->set('rate-limiting.auth.limit_per_ip', 100);

            $payload = ['email' => 'forgot@example.com'];

            $this->postJson('/api/v1/auth/forgot-password', $payload)
                ->assertOk()
                ->assertHeader('X-RateLimit-Limit', '2');

            $this->postJson('/api/v1/auth/forgot-password', $payload)
                ->assertOk()
                ->assertHeader('X-RateLimit-Limit', '2');

            $response = $this->postJson('/api/v1/auth/forgot-password', $payload);

            assertProblemResponse($response, 429, 'rate-limit-exceeded');

            expect($response->json('detail'))->toBe('Too Many Attempts.');

            $response->assertHeader('X-RateLimit-Limit', '2');
            $response->assertHeader('X-RateLimit-Remaining', '0');
            expect((int) $response->headers->get('Retry-After'))->toBeGreaterThanOrEqual(1);
            expect((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(time());
        })->group('smoke');
    });

    describe('per-ip', function (): void {
        it('returns 429 with rate-limit headers on the third distinct-email reset request from the same IP', function (): void {
            config()->set('rate-limiting.auth.limit_per_email', 100);
            config()->set('rate-limiting.auth.limit_per_ip', 2);

            $this->postJson('/api/v1/auth/forgot-password', ['email' => 'forgotip1@example.com'])
                ->assertOk()
                ->assertHeader('X-RateLimit-Limit', '2');

            $this->postJson('/api/v1/auth/forgot-password', ['email' => 'forgotip2@example.com'])
                ->assertOk()
                ->assertHeader('X-RateLimit-Limit', '2');

            $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'forgotip3@example.com']);

            assertProblemResponse($response, 429, 'rate-limit-exceeded');

            expect($response->json('detail'))->toBe('Too Many Attempts.');

            $response->assertHeader('X-RateLimit-Limit', '2');
            $response->assertHeader('X-RateLimit-Remaining', '0');
            expect((int) $response->headers->get('Retry-After'))->toBeGreaterThanOrEqual(1);
            expect((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(time());
        })->group('smoke');
    });
});

describe('reset-password rate limit', function (): void {
    describe('per-email', function (): void {
        it('returns 429 with rate-limit headers on the third identical password reset attempt', function (): void {
            config()->set('rate-limiting.auth.limit_per_email', 2);
            config()->set('rate-limiting.auth.limit_per_ip', 100);

            $payload = [
                'token' => 'token',
                'email' => 'reset@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ];

            $this->postJson('/api/v1/auth/reset-password', $payload)
                ->assertStatus(422)
                ->assertHeader('X-RateLimit-Limit', '2');

            $this->postJson('/api/v1/auth/reset-password', $payload)
                ->assertStatus(422)
                ->assertHeader('X-RateLimit-Limit', '2');

            $response = $this->postJson('/api/v1/auth/reset-password', $payload);

            assertProblemResponse($response, 429, 'rate-limit-exceeded');

            expect($response->json('detail'))->toBe('Too Many Attempts.');

            $response->assertHeader('X-RateLimit-Limit', '2');
            $response->assertHeader('X-RateLimit-Remaining', '0');
            expect((int) $response->headers->get('Retry-After'))->toBeGreaterThanOrEqual(1);
            expect((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(time());
        })->group('smoke');
    });

    describe('per-ip', function (): void {
        it('returns 429 with rate-limit headers on the third distinct-email reset attempt from the same IP', function (): void {
            config()->set('rate-limiting.auth.limit_per_email', 100);
            config()->set('rate-limiting.auth.limit_per_ip', 2);

            $this->postJson('/api/v1/auth/reset-password', [
                'token' => 'token',
                'email' => 'resetip1@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
                ->assertStatus(422)
                ->assertHeader('X-RateLimit-Limit', '2');

            $this->postJson('/api/v1/auth/reset-password', [
                'token' => 'token',
                'email' => 'resetip2@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
                ->assertStatus(422)
                ->assertHeader('X-RateLimit-Limit', '2');

            $response = $this->postJson('/api/v1/auth/reset-password', [
                'token' => 'token',
                'email' => 'resetip3@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            assertProblemResponse($response, 429, 'rate-limit-exceeded');

            expect($response->json('detail'))->toBe('Too Many Attempts.');

            $response->assertHeader('X-RateLimit-Limit', '2');
            $response->assertHeader('X-RateLimit-Remaining', '0');
            expect((int) $response->headers->get('Retry-After'))->toBeGreaterThanOrEqual(1);
            expect((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(time());
        })->group('smoke');
    });
});
