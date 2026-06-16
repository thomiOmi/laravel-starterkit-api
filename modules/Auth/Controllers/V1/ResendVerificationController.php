<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Auth')]
/**
 * @authenticated
 */
final readonly class ResendVerificationController
{
    #[Endpoint(operationId: 'resendVerification', title: 'Resend Verification Email')]
    #[Response(status: 200, description: 'Verification link sent', examples: ['status' => 200, 'message' => 'Verification link sent.', 'data' => null])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return new JsonResponse(
                [
                    'status' => SymfonyResponse::HTTP_OK,
                    'message' => __('auth.verified'),
                    'data' => null,
                ],
                SymfonyResponse::HTTP_OK,
            );
        }

        $user->sendEmailVerificationNotification();

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('auth.verification_link_sent'),
                'data' => null,
            ],
            SymfonyResponse::HTTP_OK,
        );
    }
}
