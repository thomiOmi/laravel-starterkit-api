<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ProblemResponse extends JsonResponse
{
    public function __construct(
        string $title,
        int $status,
        string $detail = '',
        string $type = 'about:blank',
        mixed $errors = null,
        string $instance = '',
    ) {
        $payload = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'message' => $title, // Add message for backward compatibility with some tests
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
