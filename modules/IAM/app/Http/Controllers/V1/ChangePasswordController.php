<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\ChangePasswordAction;
use Modules\IAM\Http\Requests\V1\ChangePasswordRequest;
use Modules\IAM\Models\User;

final readonly class ChangePasswordController extends Controller
{
    public function __construct(
        private ChangePasswordAction $changePassword,
    ) {}

    /**
     * Change the authenticated user's password.
     *
     * @return SuccessResponse<null>
     */
    public function __invoke(ChangePasswordRequest $request, #[CurrentUser] User $currentUser): SuccessResponse
    {
        $this->changePassword->handle($currentUser, $request->payload());

        return new SuccessResponse(
            data: null,
            title: __('auth.password_updated'),
            detail: __('auth.password_updated'),
        );
    }
}
