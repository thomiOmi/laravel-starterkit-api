<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\DataResponse;
use Illuminate\Http\Request;
use Modules\Auth\Resources\UserResource;
use Modules\User\Models\User;

/**
 * @tags Auth
 */
final readonly class MeController
{
    public function __invoke(Request $request): DataResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->load(['roles', 'permissions']);

        return new DataResponse(
            data: new UserResource($user),
            message: __('auth.profile_retrieved')
        );
    }
}
