<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\LogoutAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * @authenticated
 */
final readonly class LogoutController
{
    public function __construct(
        private LogoutAction $logoutAction
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->logoutAction->handle($user);

        return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
    }
}
