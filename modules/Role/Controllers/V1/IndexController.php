<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Role\Actions\ListRolesAction;
use Modules\Role\Filters\RoleFilter;
use Modules\Role\Resources\RoleResource;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class IndexController
{
    public function __construct(
        private ListRolesAction $listRoles
    ) {}

    /**
     * Display a paginated listing of the roles.
     *
     * @return SuccessResponse<AnonymousResourceCollection>
     */
    #[QueryParameter(name: 'page[number]', description: 'The page number to start the pagination from.', type: 'integer', required: false, default: 1, example: 1)]
    #[QueryParameter(name: 'page[size]', description: 'The number of results that will be returned per page.', type: 'integer', required: false, default: 10, example: 10)]
    #[QueryParameter(name: 'search', description: 'Search keyword to filter roles by name or description.', type: 'string', required: false, example: 'admin')]
    #[QueryParameter(name: 'sort', description: 'Available sorts are `name`, `created_at`. Prefix with `-` for descending order. Comma-separated for multi-column sort.', type: 'string', required: false, example: '-created_at')]
    #[Endpoint(operationId: 'listRoles', title: 'List Roles')]
    #[Response(
        status: 200,
        description: 'Paginated list of roles. Includes `meta` (pagination info) and `links` when applicable.',
        examples: [[
            'status' => 200,
            'title' => 'OK',
            'detail' => 'Roles retrieved.',
            'data' => [
                ['id' => 1, 'name' => 'admin', 'guard_name' => 'web', 'permissions' => [['id' => 1, 'name' => 'user.list', 'guard_name' => 'web']]],
                ['id' => 2, 'name' => 'editor', 'guard_name' => 'web', 'permissions' => []],
            ],
            'links' => [
                'first' => 'http://localhost/api/v1/roles?page=1',
                'last' => 'http://localhost/api/v1/roles?page=3',
                'prev' => null,
                'next' => 'http://localhost/api/v1/roles?page=2',
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 3,
                'path' => 'http://localhost/api/v1/roles',
                'per_page' => 10,
                'to' => 10,
                'total' => 25,
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
        status: 403,
        description: 'Forbidden — the user does not have the required permissions to list roles.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Forbidden',
            'status' => 403,
            'detail' => 'You are not authorised to perform this action.',
        ]],
    )]
    public function __invoke(Request $request, RoleFilter $filter): SuccessResponse
    {
        $roles = $this->listRoles->handle(
            $filter,
            $request->integer('page.size', 10),
            $request->integer('page.number', 1),
        );

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Roles']),
            RoleResource::collection($roles),
            200,
        );
    }
}
