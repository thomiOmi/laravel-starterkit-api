<?php

declare(strict_types=1);

namespace App\Payloads;

use Symfony\Component\HttpFoundation\Response;

/**
 * Payload for cached idempotent responses.
 */
final readonly class IdempotencyPayload
{
    /**
     * Create a new IdempotencyPayload instance.
     *
     * @param  int  $status  The HTTP status code.
     * @param  string  $body  The response body.
     * @param  string  $contentType  The Content-Type header value.
     * @param  string  $bodyHash  The SHA-256 hash of the request body.
     */
    public function __construct(
        public int $status,
        public string $body,
        public string $contentType,
        public string $bodyHash,
    ) {}

    /**
     * Rehydrate an IdempotencyPayload from a plain array retrieved from cache.
     *
     * Defensive checks are applied because cache entries may be corrupted,
     * manually edited, or stored by a driver that does not preserve PHP types.
     *
     * @param  array<mixed, mixed>  $data  The raw array from cache storage.
     * @return self The rehydrated payload with safe defaults.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: is_int($data['status'] ?? null) ? $data['status'] : Response::HTTP_OK,
            body: is_string($data['body'] ?? null) ? $data['body'] : '',
            contentType: is_string($data['content_type'] ?? null) ? $data['content_type'] : 'application/json',
            bodyHash: is_string($data['body_hash'] ?? null) ? $data['body_hash'] : '',
        );
    }

    /**
     * Convert the payload to a plain array for cache storage.
     *
     * Using arrays ensures compatibility with JSON-based cache drivers
     * and avoids Laravel 13+ serializable_classes restrictions.
     *
     * @return array{status: int, body: string, content_type: string, body_hash: string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'body' => $this->body,
            'content_type' => $this->contentType,
            'body_hash' => $this->bodyHash,
        ];
    }
}
