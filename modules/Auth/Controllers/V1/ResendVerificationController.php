<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ResendVerificationAction;

#[Group('Auth')]
/**
 * @authenticated
 */
final readonly class ResendVerificationController
{
    public function __construct(
        private ResendVerificationAction $resendVerificationAction
    ) {}

    #[Endpoint(operationId: 'resendVerification', title: 'Resend Verification Email')]
    #[Response(status: 200, description: 'Verification email resent successfully.', type: 'SuccessResponse<null>')]
    #[Response(
        status: 401,
        description: 'Authentication required. The request lacks a valid Bearer token.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Unauthenticated',
            'status' => 401,
            'detail' => 'You must be authenticated to access this resource.',
        ]],
    )]
    public function __invoke(Request $request): SuccessResponse
    {

        $message = $this->resendVerificationAction->handle($request->user());

        return new SuccessResponse('OK', $message);
    }
}
