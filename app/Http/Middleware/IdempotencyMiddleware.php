<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ProblemResponse;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protect mutating endpoints against duplicate submissions with idempotency keys.
 *
 * Usage: applied globally to the api group (GET/HEAD/OPTIONS and requests
 * without an `Idempotency-Key` header pass through untouched).
 *
 * - Invalid keys (not a v4 UUID) are rejected with a 422 validation response.
 * - Replayed requests with the same key and body return the stored response
 *   with the `Idempotency-Replayed: true` header.
 * - Replays with a different body return 409 Conflict.
 * - Only successful responses (2xx/3xx) are stored, so a client can retry
 *   after fixing a rejected payload.
 */
final readonly class IdempotencyMiddleware
{
    private const string UUID_V4_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $key = $request->headers->get('Idempotency-Key');

        if ($key === null || $key === '') {
            return $next($request);
        }

        if (! preg_match(self::UUID_V4_PATTERN, $key)) {
            throw ValidationException::withMessages([
                'idempotency_key' => [__('general.idempotency_invalid')],
            ]);
        }

        $cacheKey = $this->resolveCacheKey($request, $key);
        $bodyHash = hash('sha256', $request->getContent() ?: '');

        $stored = Cache::get($cacheKey);

        if (is_array($stored)) {
            return $this->resolveStored($stored, $bodyHash, $request);
        }

        $lock = Cache::lock("{$cacheKey}:lock", 10);

        try {
            $lock->block(10);
        } catch (LockTimeoutException) {
            return $this->conflict()->toResponse($request);
        }

        try {
            $stored = Cache::get($cacheKey);

            if (is_array($stored)) {
                return $this->resolveStored($stored, $bodyHash, $request);
            }

            $response = $next($request);

            if ($response->getStatusCode() < 400) {
                Cache::put($cacheKey, [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent() ?: '',
                    'content_type' => $response->headers->get('Content-Type') ?? 'application/json',
                    'body_hash' => $bodyHash,
                ], config()->integer('idempotency.ttl'));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<mixed, mixed>  $stored
     */
    private function resolveStored(array $stored, string $bodyHash, Request $request): Response
    {
        $storedBodyHash = $stored['body_hash'] ?? '';

        if (is_string($storedBodyHash) && hash_equals($storedBodyHash, $bodyHash)) {
            $status = is_int($stored['status'] ?? null) ? $stored['status'] : Response::HTTP_OK;
            $body = is_string($stored['body'] ?? null) ? $stored['body'] : '';
            $contentType = is_string($stored['content_type'] ?? null) ? $stored['content_type'] : 'application/json';

            $response = response($body, $status, ['Content-Type' => $contentType]);
            $response->headers->set('Idempotency-Replayed', 'true');

            return $response;
        }

        return $this->conflict()->toResponse($request);
    }

    private function conflict(): ProblemResponse
    {
        return new ProblemResponse(
            typeKey: 'conflict',
            title: __('auth.http_conflict'),
            status: Response::HTTP_CONFLICT,
            detail: __('general.idempotency_conflict'),
        );
    }

    private function resolveCacheKey(Request $request, string $key): string
    {
        $identifier = $request->user()?->getAuthIdentifier();

        $scope = implode('|', [
            $request->getMethod(),
            $request->path(),
            is_scalar($identifier) ? (string) $identifier : 'guest',
            strtolower($key),
        ]);

        return 'idempotency:'.hash('sha256', $scope);
    }
}
