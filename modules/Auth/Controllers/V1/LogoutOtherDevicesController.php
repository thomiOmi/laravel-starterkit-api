<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\LogoutOtherDevicesAction;
use Modules\Auth\Requests\V1\LogoutOtherDevicesRequest;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class LogoutOtherDevicesController
{
    public function __construct(
        private LogoutOtherDevicesAction $logoutOtherDevices
    ) {}

    public function __invoke(LogoutOtherDevicesRequest $request): JsonResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: SymfonyResponse::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        $this->logoutOtherDevices->handle(
            $currentUser,
            $request->string('current_password')->toString(),
        );

        return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
    }
}
