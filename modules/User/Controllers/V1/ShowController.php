<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\DataResponse;
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
    public function __invoke(User $user): DataResponse
    {
        $user->load(['roles', 'permissions']);

        return new DataResponse(
            data: new UserResource($user),
            message: __('messages.retrieved', ['resource' => 'User'])
        );
    }
}
