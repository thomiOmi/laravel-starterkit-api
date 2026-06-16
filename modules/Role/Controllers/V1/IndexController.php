<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
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
     */
    #[QueryParameter(name: 'page', description: 'The page number for pagination.', type: 'integer', required: false, default: 1, example: 1)]
    #[QueryParameter(name: 'per_page', description: 'Number of items per page.', type: 'integer', required: false, default: 10, example: 10)]
    #[QueryParameter(name: 'search', description: 'Search keyword to filter roles by name or description.', type: 'string', required: false, example: 'admin')]
    #[QueryParameter(name: 'sort', description: 'Sort columns. Prefix with - for descending order.', type: 'string', required: false, example: '-created_at')]
    #[Endpoint(operationId: 'listRoles', title: 'List Roles')]
    #[Response(status: 200, description: 'Paginated list of roles', examples: ['status' => 200, 'message' => 'Roles retrieved.', 'data' => [['id' => 1, 'name' => 'admin', 'guard_name' => 'web', 'permissions' => []]]])]
    public function __invoke(Request $request, RoleFilter $filter): JsonDataResponse
    {
        $roles = $this->listRoles->handle($filter, $request->integer('per_page', 10));

        return new JsonDataResponse(
            data: RoleResource::collection($roles),
            message: __('messages.retrieved', ['resource' => 'Roles'])
        );
    }
}
