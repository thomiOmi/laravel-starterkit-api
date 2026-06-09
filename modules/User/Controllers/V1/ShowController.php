<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;

/**
 * @tags User
 */
final readonly class ShowController
{
    /**
     * Display the specified user.
     */
    public function __invoke(User $user): JsonDataResponse
    {
        $user->load(['roles.permissions', 'permissions']);

        return new JsonDataResponse(
            data: new UserResource($user),
            message: __('messages.retrieved', ['resource' => 'User'])
        );
    }
}
