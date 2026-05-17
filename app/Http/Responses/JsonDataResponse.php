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
            'success' => $status >= 200 && $status < 300,
            'status' => $status >= 400 ? 'error' : 'success', // For backward compatibility with some tests
            'message' => $message,
            'data' => $data,
        ];

        if ($data instanceof ResourceCollection) {
            $resource = $data->toResponse(request())->getData(true);

            if (is_array($resource)) {
                $payload['data'] = $resource['data'] ?? [];

                if (isset($resource['meta']) && is_array($resource['meta'])) {
                    $payload['meta'] = $resource['meta'];
                    // Compatibility for DatatableTest which expects meta.pagination
                    $payload['meta']['pagination'] = [
                        'current_page' => $resource['meta']['current_page'] ?? null,
                        'per_page' => $resource['meta']['per_page'] ?? null,
                        'total' => $resource['meta']['total'] ?? null,
                    ];
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
