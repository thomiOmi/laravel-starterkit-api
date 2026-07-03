<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Standard Success Response.
 *
 * Provides a consistent structure for successful API responses,
 * including automatic detection and formatting of various pagination types.
 *
 * @template T of mixed
 */
final readonly class SuccessResponse implements Responsable
{
    /**
     * @param  T  $data  The main payload (Model, Collection, or Paginator).
     * @param  string  $title  A short summary of the response (Fallbacks to HTTP status text).
     * @param  string  $detail  Detailed explanation of the response.
     * @param  int  $status  The HTTP status code.
     * @param  array<string, mixed>  $extra  Additional top-level JSON fields.
     * @param  array<string, string>  $headers  Custom HTTP headers.
     */
    public function __construct(
        private mixed $data = null,
        private string $title = '',
        private string $detail = '',
        private int $status = Response::HTTP_OK,
        private array $extra = [],
        private array $headers = [],
    ) {}

    /**
     * Transform the object into a JsonResponse.
     *
     * Automatically handles data extraction and metadata generation
     * for Eloquent Resources and Paginators.
     */
    public function toResponse($request): JsonResponse
    {
        $payload = [
            'status' => $this->status,
            'title' => $this->title !== '' ? $this->title : (Response::$statusTexts[$this->status] ?? 'Success'),
            'detail' => $this->detail,
        ];

        $inner = null;

        // Data Processing & Transformation
        if ($this->data instanceof ResourceCollection) {
            $inner = $this->data->resource;

            // If ResourceCollection wraps a paginator, serialize the data specifically
            // to avoid double-nesting of links/meta within the 'data' key.
            if ($inner instanceof LengthAwarePaginator || $inner instanceof Paginator || $inner instanceof CursorPaginator) {
                $payload['data'] = $this->data->jsonSerialize();
            } else {
                $payload['data'] = $this->data->jsonSerialize();
                $inner = null;
            }
        } elseif ($this->data instanceof LengthAwarePaginator || $this->data instanceof Paginator || $this->data instanceof CursorPaginator) {
            // Memory Optimization: Access items() directly instead of toArray()
            $payload['data'] = $this->data->items();
            $inner = $this->data;
        } else {
            $payload['data'] = $this->data;
        }

        // Metadata Generation (Links & Meta)
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
            ], fn (mixed $v): bool => ! is_null($v));

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
            ], fn (mixed $v): bool => ! is_null($v));

        } elseif ($inner instanceof CursorPaginator) {
            $cursorData = $inner->toArray();
            $payload['links'] = [
                'prev' => $cursorData['prev_page_url'] ?? null,
                'next' => $cursorData['next_page_url'] ?? null,
            ];

            $payload['meta'] = array_filter([
                'path' => $inner->path(),
                'per_page' => $inner->perPage(),
                'next_cursor' => $cursorData['next_cursor'] ?? null,
                'prev_cursor' => $cursorData['prev_cursor'] ?? null,
            ], fn (mixed $v): bool => ! is_null($v));
        }

        // Merge Extra Fields with Protected Key Check
        if (! empty($this->extra)) {
            $protectedKeys = ['status', 'title', 'detail', 'data', 'meta', 'links'];
            $cleanExtra = array_diff_key($this->extra, array_flip($protectedKeys));
            $payload = array_merge($payload, $cleanExtra);
        }

        return response()->json($payload, $this->status, $this->headers);
    }
}
