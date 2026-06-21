<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\User\Actions\ShowUserAction;
use Modules\User\Resources\UserResource;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class ShowController
{
    public function __construct(
        private ShowUserAction $showUser
    ) {}

    /**
     * Display the specified user.
     */
    #[Endpoint(operationId: 'showUser', title: 'Show User')]
    #[Response(status: 200, description: 'User retrieved successfully.', type: 'SuccessResponse<UserResource>')]
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
    #[Response(
        status: 403,
        description: 'Forbidden — the user does not have the required permissions to view users.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Forbidden',
            'status' => 403,
            'detail' => 'You are not authorised to perform this action.',
        ]],
    )]
    #[Response(
        status: 404,
        description: 'User not found with the given ID.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Not Found',
            'status' => 404,
            'detail' => 'The requested resource does not exist.',
        ]],
    )]
    public function __invoke(string $user): SuccessResponse|ProblemResponse
    {
        if ((string) auth()->id() !== $user && ! auth()->user()?->can('user.view')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: 403,
                detail: __('general.forbidden'),
            );
        }

        $user = $this->showUser->handle($user);

        if (! $user) {
            return new ProblemResponse(
                title: 'Not Found',
                status: 404,
                detail: __('general.not_found', ['resource' => 'User']),
            );
        }

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'User']),
            new UserResource($user),
        );
    }
}
