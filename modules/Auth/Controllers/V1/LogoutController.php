<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        $this->logoutAction->handle($currentUser);

        return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
    }
}
