<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;

describe('auth throttle email key', function (): void {
    beforeEach(function (): void {
        config()->set('rate-limiting.auth.limit_per_email', 1);
        config()->set('rate-limiting.auth.limit_per_ip', 100);
    });

    it('shares one per-email bucket across case and whitespace variants', function (): void {
        UserFactory::new()->createOne([
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $first = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'User@Example.com',
                'password' => 'password123',
            ]);

        assertSuccessResponse($first, 200);

        $second = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/v1/auth/login', [
                'email' => ' user@example.com ',
                'password' => 'password123',
            ]);

        expect($second->status())->toBe(429);

        assertProblemResponse($second, 429, 'rate-limit-exceeded');
    });
});
