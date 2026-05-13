<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkActionRequest;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Role\Actions\CreateRoleAction;
use Modules\Role\Actions\UpdateRoleAction;
use Modules\Role\DTOs\RoleDTO;
use Modules\Role\Filters\RoleFilter;
use Modules\Role\Repositories\RoleRepository;
use Modules\Role\Requests\RoleRequest;
use Modules\Role\Resources\RoleResource;

/**
 * @tags Role
 */
class RoleController extends Controller
{
    /**
     * Create a new RoleController instance.
     */
    public function __construct(protected RoleRepository $repository) {}

    /**
     * List All Roles
     *
     * Retrieves a paginated list of all roles with filtering and sorting.
     *
     * @param  Request  $request  The request.
     * @param  RoleFilter<\Modules\Role\Models\Role>  $filter  The filter.
     */
    #[QueryParameter(name: 'page', description: 'The page number for pagination.', type: 'integer', required: false, default: 1, example: 1)]
    #[QueryParameter(name: 'per_page', description: 'Number of items per page.', type: 'integer', required: false, default: 10, example: 10)]
    #[QueryParameter(name: 'search', description: 'Search keyword to filter roles by name.', type: 'string', required: false, example: 'admin')]
    #[QueryParameter(name: 'sort_by', description: 'Column name to sort by.', type: 'string', required: false, default: 'created_at', example: 'name')]
    #[QueryParameter(name: 'sort_direction', description: 'Sort direction.', type: 'string', required: false, default: 'desc', example: 'asc')]
    public function index(Request $request, RoleFilter $filter): JsonResponse
    {
        $roles = $this->repository
            ->applyFilter($filter)
            ->paginate(
                perPage: $request->integer('per_page', 10),
                relations: ['permissions']
            );

        return $this->paginateResponse($roles, RoleResource::class, 'Roles retrieved successfully');
    }

    /**
     * Store a newly created role.
     *
     * @param  RoleRequest  $request  The role request.
     * @param  CreateRoleAction  $action  The create role action.
     */
    public function store(RoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        $dto = RoleDTO::fromRequest($request);
        $role = $action->execute($dto);

        return $this->successResponse(
            new RoleResource($role->load('permissions')),
            'Role created successfully',
            201
        );
    }

    /**
     * Display the specified role.
     *
     * @param  string|int  $id  The role ID.
     */
    public function show(string|int $id): JsonResponse
    {
        $role = $this->repository->findById($id, relations: ['permissions']);

        return $this->successResponse(
            new RoleResource($role),
            'Role retrieved successfully'
        );
    }

    /**
     * Update the specified role.
     *
     * @param  RoleRequest  $request  The role request.
     * @param  string|int  $id  The role ID.
     * @param  UpdateRoleAction  $action  The update role action.
     */
    public function update(RoleRequest $request, string|int $id, UpdateRoleAction $action): JsonResponse
    {
        $dto = RoleDTO::fromRequest($request);
        $role = $action->execute($id, $dto);

        return $this->successResponse(
            new RoleResource($role),
            'Role updated successfully'
        );
    }

    /**
     * Remove the specified role.
     *
     * @param  string|int  $id  The role ID.
     */
    public function destroy(string|int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->successResponse(null, 'Role deleted successfully');
    }

    /**
     * Perform bulk action on roles.
     *
     * @param  BulkActionRequest  $request  The validated bulk action request.
     */
    public function bulkAction(BulkActionRequest $request): JsonResponse
    {
        /** @var array{ids: array<int, string|int>, action: string} $validated */
        $validated = $request->validated();

        $count = $this->repository->bulk(
            $validated['ids'],
            $validated['action'],
        );

        $action = $validated['action'];

        return $this->successResponse(
            ['count' => $count],
            "Roles {$action} successfully"
        );
    }
}
