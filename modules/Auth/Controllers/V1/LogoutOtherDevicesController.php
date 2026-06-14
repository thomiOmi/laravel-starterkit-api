<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\LogoutOtherDevicesAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Auth
 */
final readonly class LogoutOtherDevicesController
{
    public function __construct(
        private LogoutOtherDevicesAction $logoutOtherDevices
    ) {}

    public function __invoke(Request $request): JsonDataResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->logoutOtherDevices->handle($user);

        return new JsonDataResponse(
            data: null,
            status: Response::HTTP_NO_CONTENT,
            message: __('auth.other_devices_logout_success')
        );
    }
}
