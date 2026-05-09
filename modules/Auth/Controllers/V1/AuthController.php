<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Resources\DeviceResource;
use Modules\Auth\Resources\UserResource;
use Modules\User\Models\User;

/**
 * @tags Authentication
 */
class AuthController extends Controller
{
    use ApiResponser;

    /**
     * Log the user out of the application.
     *
     * @param  Request  $request  The current request.
     * @return JsonResponse The success response.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        $currentToken->delete();

        return $this->successResponse(null, __('auth.logout_success'));
    }

    /**
     * Get the authenticated User.
     *
     * @param  Request  $request  The current request.
     * @return JsonResponse The JSON response containing user profile.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->successResponse(new UserResource($user));
    }

    /**
     * Get active devices/sessions.
     *
     * @param  Request  $request  The current request.
     * @return JsonResponse The JSON response containing devices list.
     */
    public function devices(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $devices = $user->tokens()->orderBy('last_used_at', 'desc')->get();

        return $this->successResponse(DeviceResource::collection($devices));
    }

    /**
     * Logout from a specific device.
     *
     * @param  Request  $request  The current request.
     * @param  string  $id  The device token ID.
     * @return JsonResponse The success response.
     */
    public function logoutDevice(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->tokens()->where('id', $id)->delete();

        return $this->successResponse(null, __('auth.device_logout_success'));
    }

    /**
     * Logout from all other devices.
     *
     * @param  Request  $request  The current request.
     * @return JsonResponse The success response.
     */
    public function logoutOtherDevices(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        $user->tokens()->where('id', '!=', $currentToken->id)->delete();

        return $this->successResponse(null, __('auth.other_devices_logout_success'));
    }
}
