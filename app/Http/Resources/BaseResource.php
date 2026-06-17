<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base Resource class for all API resources.
 */
class BaseResource extends JsonResource
{
    /**
     * Format a date time consistently for API responses.
     *
     * @param  \DateTimeInterface|string|null  $date  The date time to format.
     * @return string|null The formatted date "YYYY-MM-DD HH:mm:ss" or null if the input is null.
     */
    protected function formatDate(\DateTimeInterface|string|null $date): ?string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date?->format('Y-m-d H:i:s');
    }

    /**
     * {@inheritDoc}
     */
    public function toResponse($request): JsonResponse
    {
        if ($this->resource === null) {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        return new JsonResponse(
            [
                'status' => $this->statusCode(),
                'message' => $this->message(),
                'data' => $this->toArray($request),
            ],
            $this->statusCode(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function collection($resource): BaseResourceCollection
    {
        return new BaseResourceCollection($resource, static::class);
    }

    protected function statusCode(): int
    {
        /** @var int|null $statusCode */
        $statusCode = $this->additional['status_code'] ?? null;

        return is_int($statusCode) ? $statusCode : Response::HTTP_OK;
    }

    protected function message(): string
    {
        /** @var string|null $message */
        $message = $this->additional['message'] ?? null;

        return is_string($message) ? $message : __('general.success');
    }
}
