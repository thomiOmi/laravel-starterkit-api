<?php

declare(strict_types=1);

use App\Http\Middleware\IdempotencyMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\IAM\Database\Factories\UserFactory;
use Symfony\Component\HttpFoundation\Response;

covers(IdempotencyMiddleware::class);

beforeEach(function (): void {
    loginAsUser();

    Route::middleware(['auth:sanctum', 'idempotency'])->post('/_test/idempotency', fn (): JsonResponse => response()->json([
        'timestamp' => now()->toDateTimeString(),
        'random' => random_int(1, 999999),
    ]));
});

describe('replay behavior', function (): void {
    it('stores and replays with correct headers and identical body', function (): void {
        $key = (string) Str::uuid();

        $first = $this->postJson('/_test/idempotency', [], ['Idempotency-Key' => $key]);

        $first->assertOk()
            ->assertHeaderMissing('Idempotency-Replayed')
            ->assertHeaderMissing('Idempotency-Key')
            ->assertJsonPath('timestamp', fn ($ts) => filled($ts))
            ->assertJsonPath('random', fn ($r) => is_int($r));

        $replay = $this->postJson('/_test/idempotency', [], ['Idempotency-Key' => $key]);

        $replay->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertHeader('Idempotency-Key', $key)
            ->assertJsonPath('timestamp', $first->json('timestamp'))
            ->assertJsonPath('random', $first->json('random'));
    });

    it('returns 409 when key reused with different body', function (): void {
        $key = (string) Str::uuid();

        $this->postJson('/_test/idempotency', ['foo' => 'bar'], ['Idempotency-Key' => $key])
            ->assertOk();

        $response = $this->postJson('/_test/idempotency', ['foo' => 'baz'], ['Idempotency-Key' => $key]);

        assertProblemResponse($response, Response::HTTP_CONFLICT, 'conflict');

        $response->assertHeaderMissing('Idempotency-Replayed')
            ->assertJsonPath('status', Response::HTTP_CONFLICT)
            ->assertJsonPath('type', fn (string $type) => str_contains($type, 'conflict'));
    });

    it('does not cache failed responses so retry succeeds', function (): void {
        Route::middleware(['auth:sanctum', 'idempotency'])->post('/_test/idempotency-fail', function (): JsonResponse {
            if (request('fail')) {
                return response()->json(['error' => 'bad'], 422);
            }

            return response()->json(['ok' => true]);
        });

        $key = (string) Str::uuid();

        $this->postJson('/_test/idempotency-fail', ['fail' => true], ['Idempotency-Key' => $key])
            ->assertUnprocessable();

        $retry = $this->postJson('/_test/idempotency-fail', ['fail' => false], ['Idempotency-Key' => $key]);

        $retry->assertOk()
            ->assertHeaderMissing('Idempotency-Replayed')
            ->assertJsonPath('ok', true);
    });

    it('stores and replays 201 created responses', function (): void {
        Route::middleware(['auth:sanctum', 'idempotency'])->post('/_test/idempotency-create', fn (): JsonResponse => response()->json(['created' => true], 201));

        $key = (string) Str::uuid();

        $first = $this->postJson('/_test/idempotency-create', [], ['Idempotency-Key' => $key]);

        $first->assertCreated()
            ->assertHeaderMissing('Idempotency-Replayed')
            ->assertJsonPath('created', true);

        $replay = $this->postJson('/_test/idempotency-create', [], ['Idempotency-Key' => $key]);

        $replay->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('created', $first->json('created'));
    });
});

describe('user scope isolation', function (): void {
    it('prevents cross-user replay with the same key', function (): void {
        $key = (string) Str::uuid();

        $this->postJson('/_test/idempotency', [], ['Idempotency-Key' => $key])->assertOk();

        $userB = UserFactory::new()->createOne();
        $this->actingAs($userB);

        $this->postJson('/_test/idempotency', [], ['Idempotency-Key' => $key])
            ->assertOk()
            ->assertHeaderMissing('Idempotency-Replayed');
    });

    it('isolates guest requests from authenticated requests', function (): void {
        Route::middleware(['idempotency'])->post('/_test/idempotency-guest', fn (): JsonResponse => response()->json(['guest' => true]));

        $key = (string) Str::uuid();

        $this->postJson('/_test/idempotency-guest', [], ['Idempotency-Key' => $key])->assertOk();

        $user = UserFactory::new()->createOne();
        $this->actingAs($user);

        $this->postJson('/_test/idempotency-guest', [], ['Idempotency-Key' => $key])
            ->assertOk()
            ->assertHeaderMissing('Idempotency-Replayed');
    });
});
