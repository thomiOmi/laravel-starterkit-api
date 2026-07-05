<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\IAM\Actions\LogoutAction;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class LogoutController
{
    public function __construct(
        private LogoutAction $logoutAction
    ) {}

    /**
     * Auth Logout Endpoint.
     *
     * @return SuccessResponse<null>
     *
     * @throws AuthenticationException Full authentication is required.
     */
    public function __invoke(Request $request): SuccessResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        $this->logoutAction->handle($currentUser);

        return new SuccessResponse(
            data: null,
            title: 'No Content',
            detail: __('auth.logout_success'),
            status: Response::HTTP_NO_CONTENT,
        );
    }
}
