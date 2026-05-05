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

class EmailVerificationController extends Controller
{
    use ApiResponser;

    public function __construct(
        protected VerifyEmailAction $verifyEmailAction,
        protected ResendVerificationEmailAction $resendVerificationEmailAction
    ) {}

    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        $dto = new VerifyEmailDTO($id, $hash);
        $this->verifyEmailAction->execute($dto);

        return $this->successResponse(null, __('auth.verified'));
    }

    public function resend(Request $request): JsonResponse
    {
        $this->resendVerificationEmailAction->execute($request->user());

        return $this->successResponse(null, __('auth.verification_link_sent'));
    }
}
