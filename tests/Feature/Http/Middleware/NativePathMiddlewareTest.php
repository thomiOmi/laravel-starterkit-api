<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

describe('native path for opt-in middleware', function (): void {
    beforeEach(function (): void {
        Route::middleware('api')->match(['get', 'post'], '/_test/native-path', fn (): JsonResponse => response()->json(['ok' => true]));
    });

    it('serves routes that do not declare feature.flag without any flag semantics', function (): void {
        $this->getJson('/_test/native-path')
            ->assertOk()
            ->assertJson(['ok' => true]);
    });

    it('serves routes that do not declare idempotency without idempotency semantics', function (): void {
        $this->postJson('/_test/native-path', [], ['Idempotency-Key' => 'ignored-key'])
            ->assertOk()
            ->assertHeaderMissing('Idempotency-Replayed')
            ->assertJson(['ok' => true]);
    });

    it('serves routes that do not declare sunset without deprecation headers', function (): void {
        $this->getJson('/_test/native-path')
            ->assertOk()
            ->assertHeaderMissing('Sunset')
            ->assertHeaderMissing('Deprecation');
    });
});
