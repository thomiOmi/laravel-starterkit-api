<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\ShowRoleAction;
use Modules\Role\Models\Role;
use Modules\Role\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class ShowController
{
    public function __construct(
        private ShowRoleAction $showRole
    ) {}

    /**
     * Display the specified role.
     */
    #[Endpoint(operationId: 'showRole', title: 'Show Role')]
    #[Response(
        status: 200,
        description: 'Role details retrieved successfully. Includes assigned permissions.',
        examples: [[
            'status' => 200,
            'message' => 'Role retrieved.',
            'data' => ['id' => 1, 'name' => 'admin', 'guard_name' => 'web', 'permissions' => [['id' => 1, 'name' => 'user.list', 'guard_name' => 'web'], ['id' => 2, 'name' => 'user.create', 'guard_name' => 'web']]],
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
        description: 'Forbidden — the user does not have the required permissions to view roles.',
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
        description: 'Role not found with the given ID.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Not Found',
            'status' => 404,
            'message' => 'Not Found',
            'detail' => 'The requested resource does not exist.',
        ]],
    )]
    public function __invoke(Role $role): JsonResponse
    {
        $role = $this->showRole->handle($role->id);

        if ($role === null) {
            return new JsonResponse(
                [
                    'status' => SymfonyResponse::HTTP_NOT_FOUND,
                    'message' => __('general.not_found', ['resource' => 'Role']),
                    'data' => null,
                ],
                SymfonyResponse::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('general.retrieved', ['resource' => 'Role']),
                'data' => new RoleResource($role),
            ],
            SymfonyResponse::HTTP_OK,
        );
    }
}
