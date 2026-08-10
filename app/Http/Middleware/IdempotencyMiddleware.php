<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Payloads\IdempotencyPayload;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Protects mutating API endpoints against duplicate submissions using
 * idempotency keys. Compliant with standard Idempotency-Key header usage.
 *
 * - Replays with matching key and body return the stored response.
 * - Replays with mismatched body return 409 Conflict.
 * - Only 2xx/3xx responses are cached; failed requests can be retried.
 */
final readonly class IdempotencyMiddleware
{
    /** @var string The incoming request header that carries the idempotency key. */
    private const string HEADER_NAME = 'Idempotency-Key';

    /**
     * Handle an incoming request.
     *
     * Idempotency is only enforced for POST, PUT, PATCH, and DELETE.
     * Requests without an Idempotency-Key header pass through untouched.
     *
     * @param  Closure(Request): Response  $next
     *
     * @throws ValidationException If the idempotency key is not a valid UUID.
     * @throws ConflictHttpException If the key was previously used with a different body, or a lock could not be acquired.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $key = $request->headers->get(self::HEADER_NAME);

        if (blank($key)) {
            return $next($request);
        }

        if (! Str::isUuid($key, version: 4)) {
            throw ValidationException::withMessages([
                'idempotency_key' => [__('general.idempotency_invalid')],
            ]);
        }

        $cacheKey = $this->resolveCacheKey($request, $key);
        $bodyHash = $this->resolveBodyHash($request);

        if ($bodyHash === null) {
            return $next($request);
        }

        $stored = Cache::get($cacheKey);

        if (is_array($stored)) {
            return $this->resolveStored(
                IdempotencyPayload::fromArray($stored),
                $bodyHash,
                $key
            );
        }

        $lock = Cache::lock(
            "{$cacheKey}:lock",
            config()->integer('idempotency.lock_timeout', 30)
        );

        try {
            $lock->block(config()->integer('idempotency.wait_timeout', 10));
        } catch (LockTimeoutException) {
            throw $this->conflict();
        }

        try {
            $stored = Cache::get($cacheKey);

            if (is_array($stored)) {
                return $this->resolveStored(
                    IdempotencyPayload::fromArray($stored),
                    $bodyHash,
                    $key
                );
            }

            $response = $next($request);

            $this->storeResponse($cacheKey, $response, $bodyHash);

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * Compute a SHA-256 hash of the raw request body.
     *
     * Returns null when the body exceeds the allowed size limit to prevent
     * memory exhaustion attacks.
     *
     * @return string|null The body hash, or null if the body is too large.
     */
    private function resolveBodyHash(Request $request): ?string
    {
        $content = $request->getContent() ?: '';

        if (strlen($content) > config()->integer('idempotency.max_body_size', 1048576)) {
            return null;
        }

        return hash('sha256', $content);
    }

    /**
     * Reconstruct a cached response or throw a conflict if the body changed.
     *
     * @param  IdempotencyPayload  $stored  The cached response payload.
     * @param  string  $bodyHash  The hash of the current request body.
     * @param  string  $key  The original idempotency key (for response headers).
     *
     * @throws ConflictHttpException If the stored body hash does not match.
     */
    private function resolveStored(IdempotencyPayload $stored, string $bodyHash, string $key): Response
    {
        if (! hash_equals($stored->bodyHash, $bodyHash)) {
            throw $this->conflict();
        }

        $response = response($stored->body, $stored->status, ['Content-Type' => $stored->contentType]);
        $response->headers->set('Idempotency-Replayed', 'true');
        $response->headers->set(self::HEADER_NAME, $key);

        return $response;
    }

    /**
     * Cache a successful response so it can be replayed later.
     *
     * Only 2xx/3xx responses are stored. Streamed and file responses are
     * ignored because their content cannot be reliably serialized. Responses
     * larger than the size limit are also skipped to protect the cache store.
     *
     * @param  string  $cacheKey  The cache key under which to store the response.
     * @param  Response  $response  The response returned by the application.
     * @param  string  $bodyHash  The hash of the request body that produced this response.
     */
    private function storeResponse(string $cacheKey, Response $response, string $bodyHash): void
    {
        if (! $response->isSuccessful() && ! $response->isRedirection()) {
            return;
        }

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return;
        }

        $body = $response->getContent() ?: '';

        if (strlen($body) > config()->integer('idempotency.max_response_size', 2097152)) {
            return;
        }

        Cache::put($cacheKey, (new IdempotencyPayload(
            status: $response->getStatusCode(),
            body: $body,
            contentType: $response->headers->get('Content-Type') ?? 'application/json',
            bodyHash: $bodyHash,
        ))->toArray(), config()->integer('idempotency.ttl', 86400));
    }

    /**
     * Create a conflict exception for idempotency violations.
     */
    private function conflict(): ConflictHttpException
    {
        return new ConflictHttpException(__('general.idempotency_conflict'));
    }

    /**
     * Build the cache key from request context.
     *
     * The key scope includes HTTP method, path, authenticated user identifier,
     * and the idempotency key itself to prevent cross-user replay.
     *
     * @param  string  $key  The idempotency key provided by the client.
     * @return string The hashed cache key.
     */
    private function resolveCacheKey(Request $request, string $key): string
    {
        $identifier = $request->user()?->id ?: 'guest';

        $scope = implode('|', [
            $request->getMethod(),
            $request->path(),
            $identifier,
            Str::lower($key),
        ]);

        return 'idempotency:'.hash('sha256', $scope);
    }
}
