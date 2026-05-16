<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ResendVerificationEmailAction;
use Modules\Auth\Actions\VerifyEmailAction;
use Modules\Auth\DTOs\VerifyEmailDTO;

/**
 * @tags Auth
 */
class EmailVerificationController extends Controller
{
    public function verify(Request $request, VerifyEmailAction $action): JsonResponse
    {
        $dto = new VerifyEmailDTO(
            id: (string) $request->route('id'),
            hash: (string) $request->route('hash'),
            expires: (string) $request->query('expires'),
            signature: (string) $request->query('signature')
        );

        $action->execute($dto);

        return new JsonDataResponse(data: null, message: __('auth.verified'));
    }

    public function resend(Request $request, ResendVerificationEmailAction $action): JsonResponse
    {
        $action->execute($request->user());

        return new JsonDataResponse(data: null, message: __('auth.verification_link_sent'));
    }
}
