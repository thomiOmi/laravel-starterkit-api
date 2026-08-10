<?php

declare(strict_types=1);

use App\Http\Middleware\IdempotencyMiddleware;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

covers(IdempotencyMiddleware::class);

beforeEach(function (): void {
    $this->middleware = new IdempotencyMiddleware;

    Cache::swap(new Repository(new ArrayStore));
});

/**
 * Create a JSON request for direct middleware invocation.
 *
 * @param  array<string, mixed>  $body
 * @param  array<string, string>  $headers
 */
function createJsonRequest(string $method, array $body = [], array $headers = []): Request
{
    $encoded = json_encode($body);

    $request = Request::create('/api/test', $method, [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $encoded !== false ? $encoded : null);

    foreach ($headers as $key => $value) {
        $request->headers->set($key, $value);
    }

    return $request;
}

describe('http method bypass', function (): void {
    it('passes through for GET requests', function (): void {
        $response = $this->middleware->handle(
            createJsonRequest('GET'),
            fn () => response('ok')
        );

        expect($response->isOk())->toBeTrue();
    });

    it('passes through for HEAD requests', function (): void {
        $response = $this->middleware->handle(
            createJsonRequest('HEAD'),
            fn () => response('ok')
        );

        expect($response->isOk())->toBeTrue();
    });

    it('passes through for OPTIONS requests', function (): void {
        $response = $this->middleware->handle(
            createJsonRequest('OPTIONS'),
            fn () => response('ok')
        );

        expect($response->isOk())->toBeTrue();
    });
});

describe('key validation', function (): void {
    it('passes through when no key is provided', function (): void {
        $response = $this->middleware->handle(
            createJsonRequest('POST'),
            fn () => response('ok')
        );

        expect($response->isOk())->toBeTrue();
    });

    it('passes through when key is empty', function (): void {
        $response = $this->middleware->handle(
            createJsonRequest('POST', [], ['Idempotency-Key' => '']),
            fn () => response('ok')
        );

        expect($response->isOk())->toBeTrue();
    });

    it('passes through when key is whitespace-only', function (): void {
        $response = $this->middleware->handle(
            createJsonRequest('POST', [], ['Idempotency-Key' => '   ']),
            fn () => response('ok')
        );

        expect($response->isOk())->toBeTrue();
    });

    it('throws validation exception for invalid keys', function (string $invalidKey): void {
        expect(fn () => $this->middleware->handle(
            createJsonRequest('POST', [], ['Idempotency-Key' => $invalidKey]),
            fn () => response('ok')
        ))->toThrow(ValidationException::class);
    })->with([
        'plain-string' => ['not-a-uuid'],
        'v1-uuid' => ['a1b2c3d4-0000-1000-8000-000000000000'],
        'v5-uuid' => ['a1b2c3d4-0000-5000-8000-000000000000'],
        'nil-uuid' => ['00000000-0000-0000-0000-000000000000'],
        'too-short' => ['123e4567-e89b-12d3-a456'],
    ]);

    it('accepts a valid v4 uuid', function (): void {
        $key = (string) Str::uuid();

        expect($key)->toBeUuid();

        $response = $this->middleware->handle(
            createJsonRequest('POST', [], ['Idempotency-Key' => $key]),
            fn () => response('ok')
        );

        expect($response->isOk())->toBeTrue();
    });
});

describe('response caching', function (): void {
    it('stores and replays without calling next again', function (): void {
        $key = (string) Str::uuid();
        $request = createJsonRequest('POST', ['name' => 'Test'], ['Idempotency-Key' => $key]);

        $callCount = 0;
        $next = function () use (&$callCount) {
            $callCount++;

            return response()->json(['count' => $callCount]);
        };

        $first = $this->middleware->handle($request, $next);

        expect($first)->toBeInstanceOf(JsonResponse::class)
            ->and($first->isOk())->toBeTrue()
            ->and($first->headers->has('Idempotency-Replayed'))->toBeFalse();

        $firstData = json_decode((string) $first->getContent(), true);
        expect($firstData)->toHaveKey('count', 1)
            ->and($callCount)->toBe(1);

        $replay = $this->middleware->handle($request, $next);

        expect($replay->isOk())->toBeTrue()
            ->and($replay->headers->get('Idempotency-Replayed'))->toBe('true');

        $replayData = json_decode((string) $replay->getContent(), true);
        expect($replayData)->toHaveKey('count', 1)
            ->and($callCount)->toBe(1)
            ->and($replay->getContent())->toBe($first->getContent());
    });

    it('does not cache 4xx responses', function (): void {
        $key = (string) Str::uuid();
        $request = createJsonRequest('POST', [], ['Idempotency-Key' => $key]);

        $this->middleware->handle($request, fn () => response('bad', 422));

        $retry = $this->middleware->handle($request, fn () => response('ok', 200));

        expect($retry->isOk())->toBeTrue()
            ->and($retry->headers->has('Idempotency-Replayed'))->toBeFalse();
    });

    it('does not cache streamed responses', function (): void {
        $key = (string) Str::uuid();
        $request = createJsonRequest('POST', [], ['Idempotency-Key' => $key]);

        $this->middleware->handle($request, fn () => new StreamedResponse(function (): void {
            echo 'stream';
        }));

        $retry = $this->middleware->handle($request, fn () => response('ok'));

        expect($retry->isOk())->toBeTrue()
            ->and($retry->headers->has('Idempotency-Replayed'))->toBeFalse();
    });

    it('stores exact payload shape in cache', function (): void {
        $key = (string) Str::uuid();
        $request = createJsonRequest('POST', ['name' => 'Test'], ['Idempotency-Key' => $key]);

        $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $cacheKey = 'idempotency:'.hash('sha256', implode('|', [
            'POST',
            'api/test',
            'guest',
            Str::lower($key),
        ]));

        $raw = Cache::get($cacheKey);

        if (! is_array($raw)) {
            throw new RuntimeException('Cache entry should be an array');
        }

        $stored = $raw;

        expect($stored)
            ->toHaveKey('status', 200)
            ->toHaveKey('body')
            ->toHaveKey('content_type', 'application/json')
            ->toHaveKey('body_hash')
            ->and($stored['body_hash'])->toMatch('/^[a-f0-9]{64}$/i');
    });
});

describe('conflict detection', function (): void {
    it('throws conflict when key reused with different body', function (): void {
        $key = (string) Str::uuid();

        $first = createJsonRequest('POST', ['name' => 'First'], ['Idempotency-Key' => $key]);
        $this->middleware->handle($first, fn () => response()->json(['ok' => true]));

        $second = createJsonRequest('POST', ['name' => 'Second'], ['Idempotency-Key' => $key]);

        expect(fn () => $this->middleware->handle($second, fn () => response('ok')))
            ->toThrow(ConflictHttpException::class);
    });

    it('throws conflict when lock timeout occurs', function (): void {
        $key = (string) Str::uuid();
        $request = createJsonRequest('POST', [], ['Idempotency-Key' => $key]);

        $cacheKey = 'idempotency:'.hash('sha256', implode('|', [
            'POST',
            'api/test',
            'guest',
            Str::lower($key),
        ]));

        Cache::lock("{$cacheKey}:lock", 30)->block(5);

        expect(fn () => $this->middleware->handle($request, fn () => response('ok')))
            ->toThrow(ConflictHttpException::class);
    });
});

describe('body size limit', function (): void {
    it('skips idempotency when body exceeds limit', function (): void {
        config()->set('idempotency.max_body_size', 10);

        $key = (string) Str::uuid();
        $request = createJsonRequest('POST', ['name' => '12345678901'], ['Idempotency-Key' => $key]);

        $callCount = 0;
        $next = function () use (&$callCount) {
            $callCount++;

            return response()->json(['count' => $callCount]);
        };

        $this->middleware->handle($request, $next);
        $this->middleware->handle($request, $next);

        expect($callCount)->toBe(2);
    });
});
