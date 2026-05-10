<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\GetUserDevicesAction;
use Modules\Auth\Actions\LogoutAction;
use Modules\Auth\Actions\LogoutDeviceAction;
use Modules\Auth\Actions\LogoutOtherDevicesAction;
use Modules\Auth\Resources\DeviceResource;
use Modules\Auth\Resources\UserResource;

/**
 * @tags Authentication
 */
class AuthController extends Controller
{
    use ApiResponser;

    /**
     * Create a new AuthController instance.
     */
    public function __construct(
        protected LogoutAction $logoutAction,
        protected LogoutDeviceAction $logoutDeviceAction,
        protected LogoutOtherDevicesAction $logoutOtherDevicesAction,
        protected GetUserDevicesAction $getUserDevicesAction,
    ) {}

    /**
     * Log the user out of the application.
     *
     * @param  Request  $request  The current request.
     * @return JsonResponse The success response.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->logoutAction->execute($request);

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
        $devices = $this->getUserDevicesAction->execute($request);

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
        $this->logoutDeviceAction->execute($request, $id);

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
        $this->logoutOtherDevicesAction->execute($request);

        return $this->successResponse(null, __('auth.other_devices_logout_success'));
    }
}
