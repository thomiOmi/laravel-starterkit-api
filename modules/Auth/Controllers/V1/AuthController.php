<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Resources\UserResource;

class AuthController extends Controller
{
    use ApiResponser;

    /**
     * Get the authenticated User.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(new UserResource($request->user()));
    }

    /**
     * Get active devices/sessions.
     */
    public function devices(Request $request): JsonResponse
    {
        $devices = $request->user()->tokens()->orderBy('last_used_at', 'desc')->get();

        return $this->successResponse($devices);
    }

    /**
     * Logout from a specific device.
     */
    public function logoutDevice(Request $request, string $id): JsonResponse
    {
        $request->user()->tokens()->where('id', $id)->delete();

        return $this->successResponse(null, __('auth.device_logout_success'));
    }

    /**
     * Logout from all other devices.
     */
    public function logoutOtherDevices(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()->id;
        $request->user()->tokens()->where('id', '!=', $currentTokenId)->delete();

        return $this->successResponse(null, __('auth.other_devices_logout_success'));
    }
}
