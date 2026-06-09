<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\Request;
use Modules\Auth\Resources\UserResource;
use Modules\User\Models\User;

/**
 * @tags Auth
 */
final readonly class MeController
{
    public function __invoke(Request $request): JsonDataResponse
    {
        /** @var User $user */
        $user = $request->user();

        return new JsonDataResponse(
            data: new UserResource($user),
            message: __('messages.retrieved', ['resource' => 'User profile'])
        );
    }
}
