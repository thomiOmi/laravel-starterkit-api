<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\LogoutAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

#[Group('Auth')]
/**
 * @authenticated
 */
final readonly class LogoutController
{
    public function __construct(
        private LogoutAction $logoutAction
    ) {}

    #[Endpoint(operationId: 'logout', title: 'Logout')]
    #[ScrambleResponse(status: 204, description: 'User logged out successfully')]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->logoutAction->handle($user);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
