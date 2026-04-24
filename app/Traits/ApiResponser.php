<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trait for generating standardized API responses.
 */
trait ApiResponser
{
    /**
     * Return a standardized success response.
     *
     * @param  mixed  $data  The data to be returned in the response.
     * @param  string|null  $message  A descriptive message for the response.
     * @param  int  $code  The HTTP status code (default: 200).
     */
    protected function successResponse(mixed $data, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return a standardized error response.
     *
     * @param  string  $message  A descriptive error message.
     * @param  int  $code  The HTTP status code (default: 400).
     * @param  array  $errors  An array of validation or other errors.
     */
    protected function errorResponse(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Return a standardized paginated response.
     *
     * @param  LengthAwarePaginator  $paginator  The paginator instance.
     * @param  string  $resourceClass  The resource class to transform the data.
     * @param  string|null  $message  A descriptive message for the response.
     */
    protected function paginateResponse(LengthAwarePaginator $paginator, string $resourceClass, ?string $message = null): JsonResponse
    {
        // Convert the collection using the specified Resource Class
        $resourceData = $resourceClass::collection($paginator->getCollection());

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $resourceData,
            'meta' => [
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
        ]);
    }
}
