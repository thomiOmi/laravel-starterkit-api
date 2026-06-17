<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base Resource Collection that auto-wraps paginated responses in the API envelope.
 */
class BaseResourceCollection extends ResourceCollection
{
    public function __construct(mixed $resource, ?string $collects = null)
    {
        $this->collects = $collects ?? $this->collects;
        parent::__construct($resource);
    }

    /**
     * {@inheritDoc}
     */
    public function toResponse($request): JsonResponse
    {
        $payload = [
            'status' => $this->statusCode(),
            'message' => $this->message(),
            'data' => $this->collection,
        ];

        if ($this->resource instanceof LengthAwarePaginator) {
            $payload['meta'] = [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ];
        }

        return new JsonResponse($payload, $this->statusCode());
    }

    protected function statusCode(): int
    {
        return Response::HTTP_OK;
    }

    protected function message(): string
    {
        return __('general.success');
    }
}
