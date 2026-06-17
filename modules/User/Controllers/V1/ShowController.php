<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\User\Actions\ShowUserAction;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
    #[Response(
        status: 200,
        description: 'User details retrieved successfully. Includes roles and permissions when available.',
        examples: [[
            'status' => 200,
            'message' => 'User retrieved.',
            'data' => ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@example.com', 'avatar' => null, 'roles' => ['admin'], 'permissions' => ['user.view'], 'email_verified_at' => '2026-04-23 15:19:09', 'created_at' => '2026-04-23 15:19:09', 'updated_at' => '2026-04-23 15:19:09', 'deleted_at' => null],
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
    #[Response(
        status: 403,
        description: 'Forbidden — the user does not have the required permissions to view users.',
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
        description: 'User not found with the given ID.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Not Found',
            'status' => 404,
            'message' => 'Not Found',
            'detail' => 'The requested resource does not exist.',
        ]],
    )]
    public function __invoke(User $user): JsonResponse
    {
        $user = $this->showUser->handle($user->id);

        if (! $user) {
            return new JsonResponse(
                [
                    'status' => SymfonyResponse::HTTP_NOT_FOUND,
                    'message' => __('general.not_found', ['resource' => 'User']),
                    'data' => null,
                ],
                SymfonyResponse::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('general.retrieved', ['resource' => 'User']),
                'data' => new UserResource($user),
            ],
            SymfonyResponse::HTTP_OK,
        );
    }
}
