<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

final class JsonDataResponse extends JsonResponse
{
    public function __construct(
        mixed $data = null,
        int $status = Response::HTTP_OK,
        string $message = 'Success',
    ) {
        $payload = [
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];

        if ($data instanceof JsonResource) {
            // Let the resource handle its own response formatting including wrapping
            $resourceData = $data->toResponse(request())->getData(true);

            if (is_array($resourceData)) {
                $payload['data'] = $resourceData['data'] ?? $resourceData;

                if (isset($resourceData['meta']) && is_array($resourceData['meta'])) {
                    $payload['meta'] = $resourceData['meta'];
                }

                if (isset($resourceData['links'])) {
                    $payload['links'] = $resourceData['links'];
                }
            }
        }

        parent::__construct(
            data: array_filter($payload, fn ($value) => $value !== null),
            status: $status,
        );
    }
}
