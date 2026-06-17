<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\GetAuthenticatedUserAction;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Auth')]
/**
 * @authenticated
 */
final readonly class MeController
{
    public function __construct(
        private GetAuthenticatedUserAction $getAuthenticatedUser
    ) {}

    /**
     * Get the authenticated user profile.
     */
    #[Endpoint(operationId: 'me', title: 'Get Authenticated User')]
    #[Response(
        status: 200,
        description: 'Authenticated user profile retrieved successfully. Includes roles and permissions when available.',
        examples: [[
            'status' => 200,
            'message' => 'User profile retrieved.',
            'data' => [
                'id' => '01abcd',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'avatar' => null,
                'roles' => ['admin'],
                'permissions' => ['user.view', 'user.create'],
                'email_verified_at' => '2026-04-23 15:19:09',
                'created_at' => '2026-04-23 15:19:09',
                'updated_at' => '2026-04-23 15:19:09',
                'deleted_at' => null,
            ],
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
        status: 404,
        description: 'Authenticated user record not found in the database (e.g. user was deleted after authentication).',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Not Found',
            'status' => 404,
            'message' => 'Not Found',
            'detail' => 'The requested resource does not exist.',
        ]],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $profile = $this->getAuthenticatedUser->handle($user->id);

        if (! $profile) {
            return new JsonResponse(
                [
                    'status' => SymfonyResponse::HTTP_NOT_FOUND,
                    'message' => __('general.not_found', ['resource' => 'User profile']),
                    'data' => null,
                ],
                SymfonyResponse::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('general.retrieved', ['resource' => 'User profile']),
                'data' => new UserResource($profile),
            ],
            SymfonyResponse::HTTP_OK,
        );
    }
}
