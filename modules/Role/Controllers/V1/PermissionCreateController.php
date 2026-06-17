<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\CreatePermissionAction;
use Modules\Role\Requests\V1\PermissionRequest;
use Modules\Role\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Permission Management')]
/**
 * @authenticated
 */
final readonly class PermissionCreateController
{
    public function __construct(
        private CreatePermissionAction $createPermission
    ) {}

    /**
     * Store a newly created permission.
     */
    #[Endpoint(operationId: 'createPermission', title: 'Create Permission')]
    #[Response(
        status: 201,
        description: 'Permission created successfully. Returns the new permission record.',
        examples: [[
            'status' => 201,
            'message' => 'Permission created.',
            'data' => ['id' => 1, 'name' => 'post.create', 'guard_name' => 'web'],
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
        description: 'Forbidden — the user does not have the required permissions to create permissions.',
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
    public function __invoke(PermissionRequest $request): JsonResponse
    {
        $permission = $this->createPermission->handle($request->payload());

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_CREATED,
                'message' => __('general.created', ['resource' => 'Permission']),
                'data' => new PermissionResource($permission),
            ],
            SymfonyResponse::HTTP_CREATED,
        );
    }
}
