<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\LogoutAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Auth
 */
final readonly class LogoutController
{
    public function __construct(
        private LogoutAction $logoutAction
    ) {}

    public function __invoke(Request $request): JsonDataResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->logoutAction->handle($user);

        return new JsonDataResponse(
            data: null,
            status: Response::HTTP_NO_CONTENT,
            message: 'Logout successful'
        );
    }
}
