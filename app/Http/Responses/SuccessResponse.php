<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
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

        if ($data instanceof JsonResource || $data instanceof AnonymousResourceCollection) {
            $response = $data->toResponse(app('request'));
            $content = $response->getContent();

            /** @var array<string, mixed> $transformed */
            $transformed = is_string($content) ? json_decode($content, true) : [];

            $payload['data'] = $transformed['data'] ?? [];

            if (isset($transformed['links'])) {
                $payload['links'] = $transformed['links'];
            }

            if (isset($transformed['meta'])) {
                $payload['meta'] = $transformed['meta'];
            }
        } elseif ($data instanceof LengthAwarePaginator || $data instanceof Paginator || $data instanceof CursorPaginator) {
            $paginated = $data->toArray();

            $payload['data'] = $paginated['data'];

            $payload['links'] = array_filter([
                'first' => $paginated['first_page_url'] ?? null,
                'last' => $paginated['last_page_url'] ?? null,
                'prev' => $paginated['prev_page_url'] ?? null,
                'next' => $paginated['next_page_url'] ?? null,
            ]);

            $meta = [
                'per_page' => $paginated['per_page'] ?? null,
                'from' => $paginated['from'] ?? null,
                'to' => $paginated['to'] ?? null,
            ];

            if ($data instanceof LengthAwarePaginator) {
                $meta['current_page'] = $paginated['current_page'] ?? null;
                $meta['last_page'] = $paginated['last_page'] ?? null;
                $meta['total'] = $paginated['total'] ?? null;
            } elseif (! $data instanceof CursorPaginator) {
                $meta['current_page'] = $paginated['current_page'] ?? null;
            }

            if ($data instanceof CursorPaginator) {
                $meta['next_cursor'] = $paginated['next_cursor'] ?? null;
                $meta['prev_cursor'] = $paginated['prev_cursor'] ?? null;
            }

            $payload['meta'] = array_filter($meta, fn (mixed $value): bool => ! is_null($value));
        } else {
            $payload['data'] = $data;
        }

        if (! empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        parent::__construct($payload, $status);
    }
}
