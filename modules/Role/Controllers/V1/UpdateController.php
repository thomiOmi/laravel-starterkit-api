<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\Role\Actions\UpdateRoleAction;
use Modules\Role\Requests\V1\RoleRequest;
use Modules\Role\Resources\RoleResource;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class UpdateController
{
    public function __construct(
        private UpdateRoleAction $updateRole,
    ) {}

    /**
     * Update the specified role in storage.
     *
     * @param  RoleRequest  $request  The validated role update request.
     * @param  string  $role  The role ID.
     */
    #[Endpoint(operationId: 'updateRole', title: 'Update Role')]
    #[Response(status: 200, description: 'Role updated successfully. Returns the updated role with assigned permissions.', type: 'SuccessResponse<RoleResource>')]
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
        description: 'Forbidden — the user does not have the required permissions to update roles.',
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
    #[Response(
        status: 422,
        description: 'Validation error — the provided data failed validation rules.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Validation Error',
            'status' => 422,
            'detail' => 'The given data was invalid.',
            'errors' => ['name' => ['The name field is required.']],
        ]],
    )]
    public function __invoke(RoleRequest $request, string $role): SuccessResponse|ProblemResponse
    {
        $role = $this->updateRole->handle($role, $request->payload());

        if (! $role) {
            return new ProblemResponse(
                title: 'Not Found',
                status: 404,
                detail: __('general.not_found', ['resource' => 'Role']),
            );
        }

        return new SuccessResponse(
            'OK',
            __('general.updated', ['resource' => 'Role']),
            new RoleResource($role),
        );
    }
}
