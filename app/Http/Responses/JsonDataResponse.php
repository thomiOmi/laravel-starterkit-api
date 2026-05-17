<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class JsonDataResponse extends JsonResponse
{
    public function __construct(
        mixed $data = null,
        int $status = Response::HTTP_OK,
        string $message = 'Success',
    ) {
        $payload = [
            'data' => $data,
            'message' => $message,
        ];

        parent::__construct(
            data: $payload,
            status: $status,
        );
    }
}
