<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\IAM\Actions\ResendVerificationAction;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResendVerificationController
{
    public function __construct(
        private ResendVerificationAction $resendVerificationAction
    ) {}

    /**
     * @return SuccessResponse<null>
     */
    public function __invoke(Request $request): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: Response::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        $message = $this->resendVerificationAction->handle($currentUser);

        return new SuccessResponse(
            title: 'OK',
            detail: $message,
        );
    }
}
