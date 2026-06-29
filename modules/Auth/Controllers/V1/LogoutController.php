<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\LogoutAction;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class LogoutController
{
    public function __construct(
        private LogoutAction $logoutAction
    ) {}

    public function __invoke(Request $request): JsonResponse|ProblemResponse
    {
        /** @var (Authenticatable&\Modules\User\Models\User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: SymfonyResponse::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        $this->logoutAction->handle($currentUser);

        return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
    }
}
