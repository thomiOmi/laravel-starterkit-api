<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\LogoutOtherDevicesAction;
use Modules\Auth\Requests\V1\LogoutOtherDevicesRequest;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * @authenticated
 */
final readonly class LogoutOtherDevicesController
{
    public function __construct(
        private LogoutOtherDevicesAction $logoutOtherDevices
    ) {}

    public function __invoke(LogoutOtherDevicesRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->logoutOtherDevices->handle(
            $user,
            $request->string('current_password')->toString(),
        );

        return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
    }
}
