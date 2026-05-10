<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ResendVerificationEmailAction;
use Modules\Auth\Actions\VerifyEmailAction;
use Modules\Auth\DTOs\VerifyEmailDTO;

/**
 * @tags Authentication
 */
class EmailVerificationController extends Controller
{
    use ApiResponser;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected VerifyEmailAction $verifyEmailAction,
        protected ResendVerificationEmailAction $resendVerificationEmailAction
    ) {}

    /**
     * Verify user email.
     *
     * @param  Request  $request  The current request.
     * @param  string  $id  The user ID.
     * @param  string  $hash  The verification hash.
     * @return JsonResponse The success response.
     */
    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        $dto = new VerifyEmailDTO($id, $hash);
        $this->verifyEmailAction->execute($dto);

        return $this->successResponse(null, __('auth.verified'));
    }

    /**
     * Resend verification email.
     *
     * @param  Request  $request  The current request.
     * @return JsonResponse The success response.
     */
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $this->resendVerificationEmailAction->execute($user);

        return $this->successResponse(null, __('auth.verification_link_sent'));
    }
}
