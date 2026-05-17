<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\Request;
use Modules\Auth\Resources\UserResource;

/**
 * @tags Auth
 */
final readonly class MeController
{
    public function __invoke(Request $request): JsonDataResponse
    {
        return new JsonDataResponse(
            data: new UserResource($request->user()->load(['roles', 'permissions'])),
            message: 'User profile retrieved successfully'
        );
    }
}
