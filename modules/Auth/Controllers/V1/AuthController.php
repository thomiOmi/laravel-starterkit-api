<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\GetUserDevicesAction;
use Modules\Auth\Actions\LogoutAction;
use Modules\Auth\Actions\LogoutDeviceAction;
use Modules\Auth\Actions\LogoutOtherDevicesAction;
use Modules\Auth\Resources\DeviceResource;
use Modules\Auth\Resources\UserResource;
use Modules\User\Models\User;

/**
 * @tags Auth
 */
class AuthController extends Controller
{
    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $action->execute($request->user());

        return new JsonDataResponse(data: null, message: __('auth.logout_success'));
    }

    public function user(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return new JsonDataResponse(data: new UserResource($user));
    }

    public function devices(Request $request, GetUserDevicesAction $action): JsonResponse
    {
        $devices = $action->execute($request->user());

        return new JsonDataResponse(data: DeviceResource::collection($devices));
    }

    public function logoutDevice(Request $request, string $id, LogoutDeviceAction $action): JsonResponse
    {
        $action->execute($request->user(), $id);

        return new JsonDataResponse(data: null, message: __('auth.device_logout_success'));
    }

    public function logoutOtherDevices(Request $request, LogoutOtherDevicesAction $action): JsonResponse
    {
        $action->execute($request->user(), (string) $request->input('password'));

        return new JsonDataResponse(data: null, message: __('auth.other_devices_logout_success'));
    }
}
