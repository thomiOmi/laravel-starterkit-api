<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * RFC 9457 Problem Details for HTTP APIs.
 */
final class ProblemResponse extends JsonResponse
{
    public function __construct(
        string $title,
        int $status,
        string $detail = '',
        string $type = '',
        mixed $errors = null,
        string $instance = '',
    ) {
        $type = $type ?: 'https://example.com/problems';
        $payload = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'message' => $title, // Compatibility with existing tests
            'detail' => $detail,
        ];

        if ($instance !== '') {
            $payload['instance'] = $instance;
        }

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        parent::__construct(data: $payload, status: $status);

        $this->headers->set('Content-Type', 'application/problem+json');
    }
}
