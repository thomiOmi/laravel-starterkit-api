<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponser
{
    /**
     * Response sukses standar
     */
    protected function successResponse($data, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'Success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Response error standar
     */
    protected function errorResponse(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'status' => 'Error',
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Response khusus Paginasi
     */
    protected function paginateResponse(LengthAwarePaginator $paginator, $resourceClass, ?string $message = null): JsonResponse
    {
        // Mengonversi koleksi data menggunakan Resource Class yang ditentukan
        $resourceData = $resourceClass::collection($paginator->getCollection());

        return response()->json([
            'status' => 'Success',
            'message' => $message,
            'data' => $resourceData,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
