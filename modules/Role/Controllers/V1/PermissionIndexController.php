<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Modules\Role\Actions\ListPermissionsAction;
use Modules\Role\Filters\PermissionFilter;
use Modules\Role\Resources\PermissionResource;

#[Group('Permission Management')]
/**
 * @authenticated
 */
final readonly class PermissionIndexController
{
    public function __construct(
        private ListPermissionsAction $listPermissions
    ) {}

    /**
     * Display a paginated listing of permissions.
     */
    #[QueryParameter(name: 'page', description: 'The page number for pagination.', type: 'integer', required: false, default: 1, example: 1)]
    #[QueryParameter(name: 'page[number]', description: 'The page number to start the pagination from.', type: 'integer', required: false, default: 1, example: 1)]
    #[QueryParameter(name: 'page[size]', description: 'The number of results that will be returned per page.', type: 'integer', required: false, default: 20, example: 20)]
    #[QueryParameter(name: 'search', description: 'Search keyword to filter permissions by name or guard name.', type: 'string', required: false, example: 'user.view')]
    #[QueryParameter(name: 'sort', description: 'Available sorts are `name`, `guard_name`, `created_at`. Prefix with `-` for descending order. Comma-separated for multi-column sort.', type: 'string', required: false, example: 'name')]
    #[QueryParameter(name: 'filter[guard]', description: 'The guard name to filter permissions by.', type: 'string', required: false, example: 'web')]
    #[Endpoint(operationId: 'listPermissions', title: 'List Permissions')]
    #[Response(status: 200, type: 'SuccessResponse<\Illuminate\Http\Resources\Json\AnonymousResourceCollection<PermissionResource>>')]
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
        description: 'Forbidden — the user does not have the required permissions to list permissions.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Forbidden',
            'status' => 403,
            'detail' => 'You are not authorised to perform this action.',
        ]],
    )]
    public function __invoke(Request $request, PermissionFilter $filter): SuccessResponse
    {
        $permissions = $this->listPermissions->handle(
            $filter,
            $request->integer('page.size', 20),
            $request->integer('page.number', 1),
        );

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Permissions']),
            PermissionResource::collection($permissions),
            200,
        );
    }
}
