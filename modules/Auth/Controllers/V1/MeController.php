<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Modules\Auth\Actions\GetAuthenticatedUserAction;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;

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
            'title' => 'OK',
            'detail' => 'User profile retrieved.',
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
            'detail' => 'The requested resource does not exist.',
        ]],
    )]
    public function __invoke(Request $request): SuccessResponse|ProblemResponse
    {
        /** @var User $user */
        $user = $request->user();

        $profile = $this->getAuthenticatedUser->handle($user->id);

        if (! $profile) {
            return new ProblemResponse(
                title: 'Not Found',
                status: 404,
                detail: __('general.not_found', ['resource' => 'User profile']),
            );
        }

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'User profile']),
            new UserResource($profile),
        );
    }
}
