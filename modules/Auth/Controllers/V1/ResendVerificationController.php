<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Modules\User\Models\User;

#[Group('Auth')]
/**
 * @authenticated
 */
final readonly class ResendVerificationController
{
    #[Endpoint(operationId: 'resendVerification', title: 'Resend Verification Email')]
    #[Response(status: 200, description: 'Verification link sent', examples: ['status' => 200, 'message' => 'Verification link sent.', 'data' => null])]
    public function __invoke(Request $request): JsonDataResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return new JsonDataResponse(
                data: null,
                message: __('auth.verified'),
            );
        }

        $user->sendEmailVerificationNotification();

        return new JsonDataResponse(
            data: null,
            message: __('auth.verification_link_sent'),
        );
    }
}
