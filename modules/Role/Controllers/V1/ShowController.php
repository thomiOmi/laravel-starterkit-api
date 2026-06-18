<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\Role\Actions\ShowRoleAction;
use Modules\Role\Repositories\RoleRepository;
use Modules\Role\Resources\RoleResource;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class ShowController
{
    public function __construct(
        private RoleRepository $roleRepository,
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
            'title' => 'OK',
            'detail' => 'Role retrieved.',
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
            'detail' => 'The requested resource does not exist.',
        ]],
    )]
    public function __invoke(string $id): SuccessResponse|ProblemResponse
    {
        $role = $this->roleRepository->findById($id);

        if ($role === null) {
            return new ProblemResponse(
                title: 'Not Found',
                status: 404,
                detail: __('general.not_found', ['resource' => 'Role']),
            );
        }

        $role = $this->showRole->handle($role->id);

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Role']),
            new RoleResource($role),
        );
    }
}
