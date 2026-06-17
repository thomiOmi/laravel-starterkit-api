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
    #[Response(
        status: 200,
        description: 'Verification email sent (or user already verified). Returns a message indicating the result.',
        examples: [[
            'status' => 200,
            'message' => 'Verification link sent.',
            'data' => null,
        ]],
    )]
    #[Response(
        status: 401,
        description: 'Authentication required. The request lacks a valid Bearer token.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Unauthenticated',
            'status' => 401,
            'message' => 'Unauthenticated',
            'detail' => 'You must be authenticated to access this resource.',
        ]],
    )]
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
