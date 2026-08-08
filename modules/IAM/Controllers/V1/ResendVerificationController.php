<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\ResendVerificationAction;
use Modules\IAM\Models\User;

final readonly class ResendVerificationController extends Controller
{
    public function __construct(
        private ResendVerificationAction $resendVerificationAction
    ) {}

    /**
     * @return SuccessResponse<null>
     */
    public function __invoke(#[CurrentUser] User $currentUser): SuccessResponse
    {
        $message = $this->resendVerificationAction->handle($currentUser);

        return new SuccessResponse(
            title: 'OK',
            detail: $message,
        );
    }
}
