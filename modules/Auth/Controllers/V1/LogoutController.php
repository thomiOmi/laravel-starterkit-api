<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\Auth\Actions\LogoutAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class LogoutController
{
    public function __construct(
        private LogoutAction $logoutAction
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $isFrontend = EnsureFrontendRequestsAreStateful::fromFrontend($request);

        $this->logoutAction->handle($user, stateful: $isFrontend);

        if ($isFrontend) {
            session()->invalidate();

            session()->regenerateToken();
        }

        return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
    }
}
