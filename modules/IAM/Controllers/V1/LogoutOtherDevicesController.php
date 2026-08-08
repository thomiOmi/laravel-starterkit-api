<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\LogoutOtherDevicesAction;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\LogoutOtherDevicesRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class LogoutOtherDevicesController extends Controller
{
    public function __construct(
        private LogoutOtherDevicesAction $logoutOtherDevices
    ) {}

    /**
     * Log out the authenticated user from all other devices.
     *
     * @return SuccessResponse<null>
     */
    public function __invoke(LogoutOtherDevicesRequest $request, #[CurrentUser] User $currentUser): SuccessResponse
    {
        $this->logoutOtherDevices->handle($currentUser);

        return new SuccessResponse(
            data: null,
            title: __('auth.other_devices_logout_success'),
            detail: __('auth.other_devices_logout_success'),
            status: SymfonyResponse::HTTP_OK,
        );
    }
}
