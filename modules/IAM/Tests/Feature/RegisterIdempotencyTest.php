<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\User;

describe('register idempotency', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);

        config()->set('rate-limiting.auth.limit_per_email', 100);
        config()->set('rate-limiting.auth.limit_per_ip', 100);
    });

    it('replays the original registration response without creating a duplicate user', function (): void {
        $key = (string) Str::uuid();
        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ];

        $first = $this->postJson('/api/v1/auth/register', $payload, [
            'Idempotency-Key' => $key,
        ]);

        $first->assertCreated()
            ->assertHeaderMissing('Idempotency-Replayed');

        $replay = $this->postJson('/api/v1/auth/register', $payload, [
            'Idempotency-Key' => $key,
        ]);

        $replay->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertHeader('Idempotency-Key', $key);

        expect($replay->getContent())->toBe($first->getContent())
            ->and(User::where('email', 'jane@example.com')->count())->toBe(1);
    })->group('module:iam');

    it('returns a 409 problem response when the key is reused with a different body', function (): void {
        $key = (string) Str::uuid();
        $base = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ];

        $this->postJson('/api/v1/auth/register', $base, [
            'Idempotency-Key' => $key,
        ])->assertCreated();

        $response = $this->postJson('/api/v1/auth/register', [
            ...$base,
            'name' => 'Jane Doe 2',
        ], [
            'Idempotency-Key' => $key,
        ]);

        assertProblemResponse($response, 409, 'conflict');
        expect($response->headers->has('Idempotency-Replayed'))->toBeFalse()
            ->and(User::where('email', 'jane@example.com')->count())->toBe(1);
    })->group('module:iam');
});
