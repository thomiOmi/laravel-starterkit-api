<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\CreateRoleAction;
use Modules\Role\Requests\V1\RoleRequest;
use Modules\Role\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class CreateController
{
    public function __construct(
        private CreateRoleAction $createRole,
    ) {}

    /**
     * Store a newly created role in storage.
     *
     * @param  RoleRequest  $request  The validated role creation request.
     * @return JsonResponse The API response containing the new role.
     */
    #[Endpoint(operationId: 'createRole', title: 'Create Role')]
    #[Response(
        status: 201,
        description: 'Role created successfully. Returns the new role with assigned permissions.',
        examples: [[
            'status' => 201,
            'message' => 'Role created.',
            'data' => ['id' => 1, 'name' => 'editor', 'guard_name' => 'web', 'permissions' => []],
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
        description: 'Forbidden — the user does not have the required permissions to create roles.',
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
        status: 422,
        description: 'Validation error — the provided data failed validation rules.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Validation Error',
            'status' => 422,
            'message' => 'Validation Error',
            'detail' => 'The given data was invalid.',
            'errors' => ['name' => ['The name has already been taken.']],
        ]],
    )]
    public function __invoke(RoleRequest $request): JsonResponse
    {
        $role = $this->createRole->handle($request->payload());

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_CREATED,
                'message' => __('general.created', ['resource' => 'Role']),
                'data' => new RoleResource($role),
            ],
            SymfonyResponse::HTTP_CREATED,
        );
    }
}
