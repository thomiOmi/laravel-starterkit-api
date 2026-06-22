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
            $payload['data'] = $this->resolveData($data);
        }

        if (! empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        parent::__construct($payload, $status);
    }

    /**
     * Resolve data recursively if it contains JsonResource.
     */
    private function resolveData(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->resolve(app()->bound('request') ? app('request') : null);
        }

        if (is_array($data)) {
            return array_map(fn (mixed $item): mixed => $this->resolveData($item), $data);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  AbstractPaginator<int, mixed>|AbstractCursorPaginator<int, mixed>  $paginator
     */
    private function extractPagination(array &$payload, AbstractPaginator|AbstractCursorPaginator $paginator): void
    {
        $links = [];
        $meta = [
            'per_page' => $paginator->perPage(),
        ];

        if ($paginator instanceof AbstractPaginator) {
            $links['first'] = $paginator->url(1);
            $links['prev'] = $paginator->previousPageUrl();

            if (method_exists($paginator, 'nextPageUrl')) {
                $links['next'] = $paginator->nextPageUrl();
            }

            if ($paginator instanceof LengthAwarePaginator) {
                $links['last'] = $paginator->url($paginator->lastPage());
                $meta['last_page'] = $paginator->lastPage();
                $meta['total'] = $paginator->total();
            }

            $meta['from'] = $paginator->firstItem();
            $meta['to'] = $paginator->lastItem();
            $meta['current_page'] = $paginator->currentPage();
        }

        if ($paginator instanceof AbstractCursorPaginator) {
            $links['first'] = $paginator->url(null);
            $links['prev'] = $paginator->previousPageUrl();
            $links['next'] = $paginator->nextPageUrl();

            $meta['next_cursor'] = $paginator->nextCursor()?->encode();
            $meta['prev_cursor'] = $paginator->previousCursor()?->encode();
        }

        $payload['links'] = array_filter($links, fn (mixed $value): bool => ! is_null($value));
        $payload['meta'] = array_filter($meta, fn (mixed $value): bool => ! is_null($value));
    }
}
