<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\LogoutOtherDevicesAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

#[Group('Auth')]
/**
 * @authenticated
 */
final readonly class LogoutOtherDevicesController
{
    public function __construct(
        private LogoutOtherDevicesAction $logoutOtherDevices
    ) {}

    #[Endpoint(operationId: 'logoutOtherDevices', title: 'Logout Other Devices')]
    #[ScrambleResponse(status: 204, description: 'Other devices logged out successfully')]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->logoutOtherDevices->handle($user);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
