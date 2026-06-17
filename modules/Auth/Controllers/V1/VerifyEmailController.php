<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\VerifyEmailAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Auth')]
final readonly class VerifyEmailController
{
    public function __construct(
        private VerifyEmailAction $verifyEmail,
    ) {}

    #[Endpoint(operationId: 'verifyEmail', title: 'Verify Email')]
    #[Response(
        status: 200,
        description: 'Email verified successfully. The user account is now marked as verified.',
        examples: [[
            'status' => 200,
            'message' => 'Email verified.',
            'data' => ['verified' => true],
        ]],
    )]
    #[Response(
        status: 403,
        description: 'Invalid or expired verification signature. The link may have been tampered with or already used.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Forbidden',
            'status' => 403,
            'message' => 'Forbidden',
            'detail' => 'You are not authorised to perform this action.',
        ]],
    )]
    #[Response(
        status: 404,
        description: 'User not found for the given ID.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Not Found',
            'status' => 404,
            'message' => 'Not Found',
            'detail' => 'The requested resource does not exist.',
        ]],
    )]
    public function __invoke(string $id, string $hash): JsonResponse|ProblemResponse
    {
        $user = $this->verifyEmail->handle($id, $hash);

        if (! $user instanceof User) {
            return new ProblemResponse(
                title: __('auth.not_found'),
                status: SymfonyResponse::HTTP_NOT_FOUND,
                detail: 'User not found.',
            );
        }

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('auth.verified'),
                'data' => ['verified' => true],
            ],
            SymfonyResponse::HTTP_OK,
        );
    }
}
