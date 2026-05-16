<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\UpdatePasswordAction;
use Modules\Auth\Actions\UpdateProfileAction;
use Modules\Auth\Resources\UserResource;

/**
 * @tags Auth
 */
class ProfileController extends Controller
{
    public function update(Request $request, UpdateProfileAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->all());

        return new JsonDataResponse(data: ['user' => new UserResource($user)], message: __('auth.profile_updated'));
    }

    public function password(Request $request, UpdatePasswordAction $action): JsonResponse
    {
        $action->execute($request->user(), $request->all());

        return new JsonDataResponse(data: null, message: __('auth.password_updated'));
    }
}
