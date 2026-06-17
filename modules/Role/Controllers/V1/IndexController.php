<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Role\Actions\ListRolesAction;
use Modules\Role\Filters\RoleFilter;
use Modules\Role\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
            'message' => 'Roles retrieved.',
            'data' => [
                ['id' => 1, 'name' => 'admin', 'guard_name' => 'web', 'permissions' => [['id' => 1, 'name' => 'user.list', 'guard_name' => 'web']]],
                ['id' => 2, 'name' => 'editor', 'guard_name' => 'web', 'permissions' => []],
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
            'message' => 'Unauthenticated',
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
            'message' => 'Forbidden',
            'detail' => 'You are not authorised to perform this action.',
        ]],
    )]
    public function __invoke(Request $request, RoleFilter $filter): JsonResponse
    {
        $roles = $this->listRoles->handle(
            $filter,
            $request->integer('page.size', 10),
            $request->integer('page.number', 1),
        );

        $resource = RoleResource::collection($roles);
        /** @var array<string, mixed> $raw */
        $raw = $resource->toResponse($request)->getData(true);

        return new JsonResponse(
            array_filter([
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('general.retrieved', ['resource' => 'Roles']),
                'data' => $raw['data'] ?? [],
                'meta' => $raw['meta'] ?? null,
                'links' => $raw['links'] ?? null,
            ], fn ($value) => $value !== null),
            SymfonyResponse::HTTP_OK,
        );
    }
}
