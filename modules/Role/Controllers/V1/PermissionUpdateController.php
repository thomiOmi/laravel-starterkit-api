<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\Role\Actions\UpdatePermissionAction;
use Modules\Role\Repositories\PermissionRepository;
use Modules\Role\Requests\V1\PermissionRequest;
use Modules\Role\Resources\PermissionResource;

#[Group('Permission Management')]
/**
 * @authenticated
 */
final readonly class PermissionUpdateController
{
    public function __construct(
        private PermissionRepository $permissionRepository,
        private UpdatePermissionAction $updatePermission
    ) {}

    /**
     * Update the specified permission.
     */
    #[Endpoint(operationId: 'updatePermission', title: 'Update Permission')]
    #[Response(
        status: 200,
        description: 'Permission updated successfully.',
        examples: [[
            'status' => 200,
            'title' => 'OK',
            'detail' => 'Permission updated.',
            'data' => ['id' => 1, 'name' => 'user.list', 'guard_name' => 'web'],
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
        description: 'Forbidden — the user does not have the required permissions to update permissions.',
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
        description: 'Permission not found with the given ID.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Not Found',
            'status' => 404,
            'detail' => 'The requested resource does not exist.',
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
            'detail' => 'The given data was invalid.',
            'errors' => ['name' => ['The name has already been taken.']],
        ]],
    )]
    public function __invoke(PermissionRequest $request, string $id): SuccessResponse|ProblemResponse
    {
        $permission = $this->permissionRepository->findById($id);

        if ($permission === null) {
            return new ProblemResponse(
                title: 'Not Found',
                status: 404,
                detail: __('general.not_found', ['resource' => 'Permission']),
            );
        }

        $permission = $this->updatePermission->handle($permission, $request->payload());

        return new SuccessResponse(
            'OK',
            __('general.updated', ['resource' => 'Permission']),
            new PermissionResource($permission),
        );
    }
}
