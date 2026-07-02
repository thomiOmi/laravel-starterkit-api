<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template T of mixed
 */
class SuccessResponse extends JsonResponse
{
    /**
     * @param  string  $title  A short, human-readable summary of the response type.
     * @param  string  $detail  A human-readable explanation of this specific response.
     * @param  T  $data  The response payload data.
     * @param  int  $status  The HTTP status code for the response.
     * @param  array<string, mixed>  $extra  Additional top-level fields to merge into the response.
     */
    public function __construct(
        string $title,
        string $detail,
        mixed $data = null,
        int $status = Response::HTTP_OK,
        array $extra = [],
    ) {
        $payload = [
            'status' => $status,
            'title' => $title,
            'detail' => $detail,
        ];

        if ($data instanceof ResourceCollection) {
            $payload['data'] = $data->jsonSerialize();

            $inner = $data->resource;
        } elseif ($data instanceof LengthAwarePaginator || $data instanceof Paginator || $data instanceof CursorPaginator) {
            $payload['data'] = $data->toArray()['data'];
            $inner = $data;
        } else {
            $payload['data'] = $data;
            $inner = null;
        }

        if ($inner instanceof LengthAwarePaginator) {
            $payload['links'] = [
                'first' => $inner->url(1),
                'last' => $inner->url($inner->lastPage()),
                'prev' => $inner->previousPageUrl(),
                'next' => $inner->nextPageUrl(),
            ];

            $payload['meta'] = array_filter([
                'current_page' => $inner->currentPage(),
                'from' => $inner->firstItem(),
                'last_page' => $inner->lastPage(),
                'path' => $inner->path(),
                'per_page' => $inner->perPage(),
                'to' => $inner->lastItem(),
                'total' => $inner->total(),
            ], fn (mixed $value): bool => ! is_null($value));
        } elseif ($inner instanceof Paginator) {
            $payload['links'] = [
                'first' => $inner->url(1),
                'prev' => $inner->previousPageUrl(),
                'next' => $inner->nextPageUrl(),
            ];

            $payload['meta'] = array_filter([
                'current_page' => $inner->currentPage(),
                'from' => $inner->firstItem(),
                'path' => $inner->path(),
                'per_page' => $inner->perPage(),
                'to' => $inner->lastItem(),
            ], fn (mixed $value): bool => ! is_null($value));
        } elseif ($inner instanceof CursorPaginator) {
            $paginated = $inner->toArray();

            $payload['links'] = [
                'prev' => $paginated['prev_page_url'] ?? null,
                'next' => $paginated['next_page_url'] ?? null,
            ];

            $payload['meta'] = array_filter([
                'path' => $inner->path(),
                'per_page' => $inner->perPage(),
                'next_cursor' => $paginated['next_cursor'] ?? null,
                'prev_cursor' => $paginated['prev_cursor'] ?? null,
            ], fn (mixed $value): bool => ! is_null($value));
        }

        if (! empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        parent::__construct($payload, $status);
    }
}
