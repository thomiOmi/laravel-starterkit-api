<?php

declare(strict_types=1);

use App\Http\Middleware\IdempotencyMiddleware;
use Illuminate\Support\Str;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\User;

covers([IdempotencyMiddleware::class]);

describe('idempotency middleware', function (): void {
    beforeEach(function (): void {
        loginAsUser();
    });

    describe('key validation', function (): void {
        it('rejects an invalid Idempotency-Key with a 422 problem response', function (): void {
            $response = $this->putJson('/api/v1/auth/me', [
                'name' => 'New Name',
            ], ['Idempotency-Key' => 'not-a-uuid']);

            assertProblemResponse($response, 422, 'validation-failed');

            expect($response->json('errors.idempotency_key'))
                ->toBeArray()
                ->toHaveCount(1);
        });

        it('rejects a non-v4 UUID with a 422 problem response', function (): void {
            $response = $this->putJson('/api/v1/auth/me', [
                'name' => 'New Name',
            ], ['Idempotency-Key' => 'a1b2c3d4-0000-1000-8000-000000000000']);

            assertProblemResponse($response, 422, 'validation-failed');
        });
    });

    describe('replay', function (): void {
        it('stores a successful response and replays it with the Idempotency-Replayed header', function (): void {
            $key = (string) Str::uuid();

            $first = $this->putJson('/api/v1/auth/me', [
                'name' => 'Replay Name',
            ], ['Idempotency-Key' => $key]);

            $first->assertOk()
                ->assertHeaderMissing('Idempotency-Replayed');
            expect($first->json('data.user.name'))->toBe('Replay Name');

            $replay = $this->putJson('/api/v1/auth/me', [
                'name' => 'Replay Name',
            ], ['Idempotency-Key' => $key]);

            $replay->assertOk()
                ->assertHeader('Idempotency-Replayed', 'true');
            expect($replay->getContent())->toBe($first->getContent());
        });

        it('returns 409 when the same key is reused with a different body', function (): void {
            $key = (string) Str::uuid();

            $this->putJson('/api/v1/auth/me', [
                'name' => 'First Name',
            ], ['Idempotency-Key' => $key])->assertOk();

            $response = $this->putJson('/api/v1/auth/me', [
                'name' => 'Second Name',
            ], ['Idempotency-Key' => $key]);

            assertProblemResponse($response, 409, 'conflict');
            expect($response->headers->has('Idempotency-Replayed'))->toBeFalse();
        });

        it('does not store failed responses so a corrected retry succeeds', function (): void {
            $key = (string) Str::uuid();

            $this->putJson('/api/v1/auth/me', [
                'name' => '',
            ], ['Idempotency-Key' => $key])->assertUnprocessable();

            $response = $this->putJson('/api/v1/auth/me', [
                'name' => 'Corrected Name',
            ], ['Idempotency-Key' => $key]);

            $response->assertOk()
                ->assertHeaderMissing('Idempotency-Replayed');
            expect($response->json('data.user.name'))->toBe('Corrected Name');
        });
    });

    describe('scope', function (): void {
        it('passes read requests through without validating or storing', function (): void {
            $response = $this->getJson('/api/v1/auth/me', [
                'Idempotency-Key' => 'not-a-uuid',
            ]);

            $response->assertOk();
        });

        it('passes mutating requests through when no key is provided', function (): void {
            $response = $this->putJson('/api/v1/auth/me', [
                'name' => 'No Key Name',
            ]);

            $response->assertOk();
        });
    });

    describe('route level', function (): void {
        beforeEach(function (): void {
            $this->seed(IAMSeeder::class);
        });

        it('prevents duplicate registrations on replay of the same key', function (): void {
            $key = (string) Str::uuid();
            $payload = [
                'name' => 'Idempotent Registrant',
                'email' => 'idempotent@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ];

            $first = $this->postJson('/api/v1/auth/register', $payload, [
                'Idempotency-Key' => $key,
            ]);

            $first->assertCreated();

            $replay = $this->postJson('/api/v1/auth/register', $payload, [
                'Idempotency-Key' => $key,
            ]);

            $replay->assertCreated()
                ->assertHeader('Idempotency-Replayed', 'true');
            expect($replay->getContent())->toBe($first->getContent())
                ->and(User::query()->where('email', 'idempotent@example.com')->count())->toBe(1);
        });
    });
});
