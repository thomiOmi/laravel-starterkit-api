<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Role\Actions\ListPermissionsAction;
use Modules\Role\Filters\PermissionFilter;
use Modules\Role\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
    #[QueryParameter(name: 'per_page', description: 'Number of items per page. Defaults to 20 for permissions.', type: 'integer', required: false, default: 20, example: 20)]
    #[QueryParameter(name: 'search', description: 'Search keyword to filter permissions by name or guard name.', type: 'string', required: false, example: 'user.view')]
    #[QueryParameter(name: 'sort', description: 'Sort columns. Prefix with - for descending order. Comma-separated for multi-column sort.', type: 'string', required: false, example: 'name')]
    #[QueryParameter(name: 'filter[guard]', description: 'Filter by guard name.', type: 'string', required: false, example: 'web')]
    #[Endpoint(operationId: 'listPermissions', title: 'List Permissions')]
    #[Response(status: 200, description: 'Paginated list of permissions', examples: ['status' => 200, 'message' => 'Permissions retrieved.', 'data' => [['id' => 1, 'name' => 'user.list', 'guard_name' => 'web']]])]
    public function __invoke(Request $request, PermissionFilter $filter): JsonResponse
    {
        $permissions = $this->listPermissions->handle($filter, $request->integer('per_page', 20));

        $resource = PermissionResource::collection($permissions);
        /** @var array<string, mixed> $raw */
        $raw = $resource->toResponse($request)->getData(true);

        return new JsonResponse(
            array_filter([
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('general.retrieved', ['resource' => 'Permissions']),
                'data' => $raw['data'] ?? [],
                'meta' => $raw['meta'] ?? null,
                'links' => $raw['links'] ?? null,
            ], fn ($value) => $value !== null),
            SymfonyResponse::HTTP_OK,
        );
    }
}
