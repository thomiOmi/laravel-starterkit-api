<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\DeletePermissionAction;
use Modules\Role\Models\Permission;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Permission Management')]
/**
 * @authenticated
 */
final readonly class PermissionDeleteController
{
    public function __construct(
        private DeletePermissionAction $deletePermission
    ) {}

    /**
     * Remove the specified permission.
     */
    #[Endpoint(operationId: 'deletePermission', title: 'Delete Permission')]
    #[Response(
        status: 204,
        description: 'Permission deleted successfully. No content is returned.',
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
        description: 'Forbidden — the permission cannot be deleted (e.g., protected system permission).',
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
        description: 'Permission not found with the given ID (handled by route model binding).',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Not Found',
            'status' => 404,
            'detail' => 'The requested resource does not exist.',
        ]],
    )]
    public function __invoke(Permission $permission): JsonResponse
    {
        if ($this->deletePermission->handle($permission)) {
            return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
        }

        return new ProblemResponse(
            title: 'Forbidden',
            status: 403,
            detail: __('general.delete_error', ['resource' => 'Permission']),
        );
    }
}
