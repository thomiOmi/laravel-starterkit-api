<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\IAM\Actions\LogoutOtherDevicesAction;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\LogoutOtherDevicesRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class LogoutOtherDevicesController
{
    public function __construct(
        private LogoutOtherDevicesAction $logoutOtherDevices
    ) {}

    /**
     * Log out the authenticated user from all other devices.
     *
     * @return SuccessResponse<null>
     */
    public function __invoke(LogoutOtherDevicesRequest $request): SuccessResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        $this->logoutOtherDevices->handle(
            $currentUser,
            $request->string('current_password')->toString(),
        );

        return new SuccessResponse(null, status: SymfonyResponse::HTTP_NO_CONTENT);
    }
}
