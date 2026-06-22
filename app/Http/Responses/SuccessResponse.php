<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template TData = mixed
 */
class SuccessResponse extends JsonResponse
{
    /**
     * @param  TData  $data
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
            $payload['data'] = $data->resolve(app()->bound('request') ? app('request') : null);

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
     * Extract pagination metadata and links from the paginator.
     *
     * @param  array<string, mixed>  $payload  The response payload to update.
     * @param  AbstractPaginator<int, mixed>|AbstractCursorPaginator<int, mixed>  $paginator  The paginator instance.
     */
    private function extractPagination(array &$payload, AbstractPaginator|AbstractCursorPaginator $paginator): void
    {
        $links = [];
        $meta = [];

        if (method_exists($paginator, 'url')) {
            $links['first'] = $paginator instanceof AbstractPaginator ? $paginator->url(1) : $paginator->url(null);
            $links['last'] = $paginator instanceof LengthAwarePaginator ? $paginator->url($paginator->lastPage()) : null;
        }

        $links['prev'] = $paginator->previousPageUrl();
        $links['next'] = method_exists($paginator, 'nextPageUrl') ? $paginator->nextPageUrl() : null;

        $meta['per_page'] = $paginator->perPage();

        if ($paginator instanceof AbstractPaginator) {
            $meta['current_page'] = $paginator->currentPage();
            $meta['from'] = $paginator->firstItem();
            $meta['to'] = $paginator->lastItem();

            if ($paginator instanceof LengthAwarePaginator) {
                $meta['last_page'] = $paginator->lastPage();
                $meta['total'] = $paginator->total();
            }
        } elseif ($paginator instanceof AbstractCursorPaginator) {
            $meta['next_cursor'] = $paginator->nextCursor()?->encode();
            $meta['prev_cursor'] = $paginator->previousCursor()?->encode();
        }

        $payload['links'] = array_filter($links, fn (mixed $value): bool => ! is_null($value));
        $payload['meta'] = array_filter($meta, fn (mixed $value): bool => ! is_null($value));
    }
}
