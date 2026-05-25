<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
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

        if ($data instanceof ResourceCollection) {
            $resource = $data->toResponse(request())->getData(true);

            if (is_array($resource)) {
                $payload['data'] = $resource['data'] ?? [];

                if (isset($resource['meta']) && is_array($resource['meta'])) {
                    $payload['meta'] = $resource['meta'];
                }

                if (isset($resource['links'])) {
                    $payload['links'] = $resource['links'];
                }
            }
        }

        parent::__construct(
            data: array_filter($payload, fn ($value) => $value !== null),
            status: $status,
        );
    }
}
