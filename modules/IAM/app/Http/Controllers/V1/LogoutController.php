<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\LogoutAction;
use Modules\IAM\Models\User;

final readonly class LogoutController extends Controller
{
    public function __construct(
        private LogoutAction $logoutAction
    ) {}

    /**
     * Auth Logout Endpoint.
     *
     * @return SuccessResponse<null>
     */
    public function __invoke(#[CurrentUser] User $currentUser): SuccessResponse
    {
        $this->logoutAction->handle($currentUser);

        return new SuccessResponse(
            data: null,
            title: 'OK',
            detail: __('auth.logout_success'),
        );
    }
}
