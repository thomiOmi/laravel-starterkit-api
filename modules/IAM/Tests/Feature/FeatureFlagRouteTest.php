<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Laravel\Pennant\Feature;
use Modules\IAM\Database\Seeders\IAMSeeder;

describe('self-registration feature flag', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);

        Config::set('rate-limiting.auth.limit_per_email', 100);
        Config::set('rate-limiting.auth.limit_per_ip', 100);
    });

    it('allows registration when the flag is active', function (): void {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        assertSuccessResponse($response, 201, 'Created');
    })->group('module:iam');

    it('rejects registration with a problem response when the flag is deactivated', function (): void {
        Feature::deactivate('iam.self-registration');

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        assertProblemResponse($response, 403);
    })->group('module:iam');

    it('allows registration again once the flag is reactivated', function (): void {
        Feature::deactivate('iam.self-registration');
        Feature::activate('iam.self-registration');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertCreated();
    })->group('module:iam');
});
