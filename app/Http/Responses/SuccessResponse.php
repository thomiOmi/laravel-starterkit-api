<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template T
 */
final readonly class SuccessResponse implements Responsable
{
    /**
     * @param  T  $data
     * @param  array<string, mixed>  $extra
     * @param  array<string, string|int|array<int, string>|null>  $headers
     */
    public function __construct(
        private mixed $data = null,
        private ?string $title = null,
        private ?string $detail = null,
        private int $status = Response::HTTP_OK,
        private array $extra = [],
        private array $headers = [],
    ) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse|\Illuminate\Http\Response
    {
        if ($this->status === Response::HTTP_NO_CONTENT || $this->status === Response::HTTP_RESET_CONTENT) {
            return response()->noContent(status: $this->status, headers: $this->normalizeHeaders());
        }

        $payload = [
            'status' => $this->status,
        ];

        if ($this->title !== null) {
            $payload['title'] = $this->title;
        }

        if ($this->detail !== null) {
            $payload['detail'] = $this->detail;
        }

        $payload['data'] = $this->resolveData();

        $paginator = $this->resolveTargetForMeta();
        if ($paginator !== null) {
            $payload['meta'] = $this->formatPaginationMeta($paginator);
        }

        if ($this->extra !== []) {
            $protectedKeys = ['status', 'title', 'detail', 'data', 'meta'];
            $cleanExtra = array_diff_key($this->extra, array_flip($protectedKeys));
            $payload = array_merge($payload, $cleanExtra);
        }

        return response()->json($payload, $this->status, $this->normalizeHeaders());
    }

    /**
     * Convert mixed header values into the string shape expected by the response.
     *
     * @return array<string, string|array<int, string>>
     */
    private function normalizeHeaders(): array
    {
        $headers = [];

        foreach ($this->headers as $key => $value) {
            if ($value === null) {
                continue;
            }

            $headers[$key] = is_array($value)
                ? array_values(array_map('strval', $value))
                : strval($value);
        }

        return $headers;
    }

    private function resolveData(): mixed
    {
        if ($this->data instanceof ResourceCollection) {
            return $this->data->jsonSerialize();
        }

        if ($this->data instanceof AbstractPaginator || $this->data instanceof CursorPaginator) {
            return $this->data->items();
        }

        return $this->data;
    }

    /**
     * @return LengthAwarePaginator<int|string, mixed>|Paginator<int|string, mixed>|CursorPaginator<int|string, mixed>|null
     */
    private function resolveTargetForMeta(): LengthAwarePaginator|Paginator|CursorPaginator|null
    {
        $target = $this->data;

        if ($target instanceof ResourceCollection) {
            $target = $target->resource;
        }

        if ($target instanceof LengthAwarePaginator || $target instanceof Paginator || $target instanceof CursorPaginator) {
            return $target;
        }

        return null;
    }

    /**
     * @param  LengthAwarePaginator<int|string, mixed>|Paginator<int|string, mixed>|CursorPaginator<int|string, mixed>  $paginator
     * @return array<string, mixed>
     */
    private function formatPaginationMeta(mixed $paginator): array
    {
        if ($paginator instanceof LengthAwarePaginator) {
            return $this->formatLengthAware($paginator);
        }

        if ($paginator instanceof Paginator) {
            return $this->formatSimple($paginator);
        }

        return $this->formatCursor($paginator);
    }

    /**
     * @param  LengthAwarePaginator<int|string, mixed>  $paginator
     * @return array{current_page: int, last_page: int, per_page: int, total: int, has_more: bool}
     */
    private function formatLengthAware(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'has_more' => $paginator->hasMorePages(),
        ];
    }

    /**
     * @param  Paginator<int|string, mixed>  $paginator
     * @return array{current_page: int, per_page: int, has_more: bool}
     */
    private function formatSimple(Paginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'has_more' => $paginator->hasMorePages(),
        ];
    }

    /**
     * @param  CursorPaginator<int|string, mixed>  $paginator
     * @return array{per_page: int, next_cursor: ?string, prev_cursor: ?string, has_more: bool}
     */
    private function formatCursor(CursorPaginator $paginator): array
    {
        $cursorData = $paginator->toArray();

        return [
            'per_page' => $paginator->perPage(),
            'next_cursor' => isset($cursorData['next_cursor']) && is_string($cursorData['next_cursor']) ? $cursorData['next_cursor'] : null,
            'prev_cursor' => isset($cursorData['prev_cursor']) && is_string($cursorData['prev_cursor']) ? $cursorData['prev_cursor'] : null,
            'has_more' => $paginator->hasMorePages(),
        ];
    }
}
