<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\IAM\Actions\ResendVerificationAction;
use Modules\IAM\Models\User;

final readonly class ResendVerificationController
{
    public function __construct(
        private ResendVerificationAction $resendVerificationAction
    ) {}

    /**
     * @return SuccessResponse<null>
     */
    public function __invoke(Request $request): SuccessResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        $message = $this->resendVerificationAction->handle($currentUser);

        return new SuccessResponse(
            title: 'OK',
            detail: $message,
        );
    }
}
