<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RFC 9457 Problem Details for HTTP APIs.
 *
 * Provides a standardized error response format.
 */
final readonly class ProblemResponse implements Responsable
{
    /**
     * @param  string  $typeKey  Key mapped in config/errors.php
     * @param  string|null  $title  Short summary
     * @param  int  $status  HTTP status code
     * @param  string  $detail  Human-readable explanation
     * @param  array<string, mixed>  $extensions  Additional RFC 9457 extension members
     * @param  string  $instance  A URI reference identifying the specific occurrence
     * @param  array<string, string>  $headers  Custom HTTP headers
     */
    public function __construct(
        private string $typeKey = 'default',
        private ?string $title = null,
        private int $status = Response::HTTP_BAD_REQUEST,
        private string $detail = '',
        private array $extensions = [],
        private string $instance = '',
        private array $headers = [],
    ) {}

    /**
     * Transform the object into a JsonResponse.
     *
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse
    {
        $title = $this->title ?: (Response::$statusTexts[$this->status] ?? 'Unknown Error');

        $payload = [
            'type' => $this->resolveTypeUri(),
            'title' => $title,
            'status' => $this->status,
            'detail' => $this->detail,
            'timestamp' => now()->toJSON(),
        ];

        if ($this->instance !== '') {
            $payload['instance'] = $this->instance;
        }

        if (! empty($this->extensions)) {
            $protectedKeys = ['status', 'title', 'detail', 'type', 'instance', 'timestamp'];

            $processedExtensions = array_map(function (mixed $value) {
                return $value instanceof Arrayable ? $value->toArray() : $value;
            }, $this->extensions);

            $cleanExtension = array_diff_key($processedExtensions, array_flip($protectedKeys));
            $payload = array_merge($payload, $cleanExtension);
        }

        return response()->json($payload, $this->status, array_merge($this->headers, [
            'Content-Type' => 'application/problem+json',
        ]));
    }

    /**
     * Static factory method.
     *
     * @param  array<string, mixed>  $extensions
     * @param  array<string, string>  $headers
     */
    public static function make(
        string $typeKey = 'default',
        ?string $title = null,
        int $status = Response::HTTP_BAD_REQUEST,
        string $detail = '',
        array $extensions = [],
        string $instance = '',
        array $headers = [],
    ): self {
        return new self($typeKey, $title, $status, $detail, $extensions, $instance, $headers);
    }

    private function resolveTypeUri(): string
    {
        if ($this->typeKey === 'about:blank') {
            return 'about:blank';
        }

        $base = rtrim(config()->string('errors.docs_url', ''), '/');

        $slug = config()->string("errors.types.{$this->typeKey}", 'general-error');

        return "{$base}/{$slug}";
    }
}
