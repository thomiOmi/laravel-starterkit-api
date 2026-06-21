<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

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

            if ($resource instanceof LengthAwarePaginator || $resource instanceof Paginator || $resource instanceof CursorPaginator || $resource instanceof AbstractPaginator || $resource instanceof AbstractCursorPaginator) {
                $this->extractPagination($payload, $resource);
            }
        } elseif ($data instanceof LengthAwarePaginator || $data instanceof Paginator || $data instanceof CursorPaginator) {
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
    private function extractPagination(array &$payload, mixed $paginator): void
    {
        $payload['links'] = array_filter([
            'first' => method_exists($paginator, 'url') ? $paginator->url(1) : null,
            'last' => $paginator instanceof LengthAwarePaginator ? $paginator->url($paginator->lastPage()) : null,
            'prev' => method_exists($paginator, 'previousPageUrl') ? $paginator->previousPageUrl() : null,
            'next' => method_exists($paginator, 'nextPageUrl') ? $paginator->nextPageUrl() : null,
        ]);

        $meta = [
            'per_page' => method_exists($paginator, 'perPage') ? $paginator->perPage() : null,
            'from' => method_exists($paginator, 'firstItem') ? $paginator->firstItem() : null,
            'to' => method_exists($paginator, 'lastItem') ? $paginator->lastItem() : null,
        ];

        if ($paginator instanceof LengthAwarePaginator) {
            $meta['current_page'] = $paginator->currentPage();
            $meta['last_page'] = $paginator->lastPage();
            $meta['total'] = $paginator->total();
        } elseif (method_exists($paginator, 'currentPage')) {
            $meta['current_page'] = $paginator->currentPage();
        }

        if ($paginator instanceof CursorPaginator || $paginator instanceof AbstractCursorPaginator) {
            $payload['links']['next'] = $paginator->nextPageUrl();
            $payload['links']['prev'] = $paginator->previousPageUrl();

            $meta['next_cursor'] = $paginator->nextCursor()?->encode();
            $meta['prev_cursor'] = $paginator->prevCursor()?->encode();
        }

        $payload['meta'] = array_filter($meta, fn (mixed $value): bool => ! is_null($value));
    }
}
