<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

class SuccessResponse extends JsonResponse
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        string $title,
        string $detail,
        mixed $data = null,
        int $status = 200,
        array $extra = [],
    ) {
        $payload = [
            'status' => $status,
            'title' => $title,
            'detail' => $detail,
        ];

        if ($data instanceof JsonResource) {
            $payload['data'] = $data->resolve(app('request'));

            $resource = $data->resource;

            if ($resource instanceof AbstractPaginator || $resource instanceof AbstractCursorPaginator) {
                $this->extractPagination($payload, $resource);
            }
        } elseif ($data instanceof AbstractPaginator || $data instanceof AbstractCursorPaginator) {
            $payload['data'] = $data->items();
            $this->extractPagination($payload, $data);
        } else {
            $payload['data'] = $data;
        }

        if (! empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        parent::__construct($payload, $status);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractPagination(array &$payload, AbstractPaginator|AbstractCursorPaginator $paginator): void
    {
        $payload['links'] = array_filter([
            'first' => $paginator instanceof AbstractPaginator ? $paginator->url(1) : null,
            'last' => $paginator instanceof LengthAwarePaginator ? $paginator->url($paginator->lastPage()) : null,
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ]);

        $meta = [
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        if ($paginator instanceof LengthAwarePaginator) {
            $meta['current_page'] = $paginator->currentPage();
            $meta['last_page'] = $paginator->lastPage();
            $meta['total'] = $paginator->total();
        } elseif ($paginator instanceof AbstractPaginator) {
            $meta['current_page'] = $paginator->currentPage();
        }

        if ($paginator instanceof AbstractCursorPaginator) {
            $meta['next_cursor'] = $paginator->nextCursor()?->encode();
            $meta['prev_cursor'] = $paginator->previousCursor()?->encode();
        }

        $payload['meta'] = array_filter($meta, fn (mixed $value): bool => ! is_null($value));
    }
}
