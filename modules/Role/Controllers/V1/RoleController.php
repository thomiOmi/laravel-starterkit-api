<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Role\Actions\CreateRoleAction;
use Modules\Role\Actions\UpdateRoleAction;
use Modules\Role\DTOs\RoleDTO;
use Modules\Role\Filters\RoleFilter;
use Modules\Role\Repositories\RoleRepository;
use Modules\Role\Requests\RoleRequest;
use Modules\Role\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Role
 */
class RoleController extends Controller
{
    /**
     * Create a new RoleController instance.
     *
     * @param  RoleRepository  $roleRepository  The role repository.
     */
    public function __construct(protected RoleRepository $roleRepository) {}

    /**
     * Display a paginated listing of the roles.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  RoleFilter  $filter  The role filters.
     */
    public function index(Request $request, RoleFilter $filter): JsonResponse
    {
        $roles = $this->roleRepository
            ->applyFilter($filter)
            ->paginate(perPage: $request->integer('per_page', 10));

        return new JsonDataResponse(data: RoleResource::collection($roles), message: 'Roles retrieved successfully');
    }

    /**
     * Store a newly created role in storage.
     *
     * @param  RoleRequest  $request  The role request.
     * @param  CreateRoleAction  $action  The create role action.
     * @return JsonResponse The JSON response containing the created role.
     */
    public function store(RoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        $dto = RoleDTO::fromRequest($request);
        $role = $action->execute($dto);

        return new JsonDataResponse(
            data: new RoleResource($role),
            status: Response::HTTP_CREATED,
            message: 'Role created successfully'
        );
    }

    /**
     * Display the specified role.
     *
     * @param  string|int  $id  The role ID.
     * @return JsonResponse The JSON response containing the role.
     */
    public function show(string|int $id): JsonResponse
    {
        $role = $this->roleRepository->findById($id);

        return new JsonDataResponse(
            data: new RoleResource($role),
            message: 'Role retrieved successfully'
        );
    }

    /**
     * Update the specified role in storage.
     *
     * @param  RoleRequest  $request  The role request.
     * @param  string|int  $id  The role ID.
     * @param  UpdateRoleAction  $action  The update role action.
     * @return JsonResponse The JSON response containing the updated role.
     */
    public function update(RoleRequest $request, string|int $id, UpdateRoleAction $action): JsonResponse
    {
        $dto = RoleDTO::fromRequest($request);
        $role = $action->execute($id, $dto);

        return new JsonDataResponse(
            data: new RoleResource($role),
            message: 'Role updated successfully'
        );
    }

    /**
     * Remove the specified role from storage.
     *
     * @param  string|int  $id  The role ID.
     * @return JsonResponse The JSON response indicating success.
     */
    public function destroy(string|int $id): JsonResponse
    {
        $this->roleRepository->delete($id);

        return new JsonDataResponse(data: null, message: 'Role deleted successfully');
    }

    /**
     * Perform bulk action on roles.
     *
     * @param  BulkActionRequest  $request  The validated bulk action request.
     * @return JsonResponse The JSON response containing the result of the bulk action.
     */
    public function bulkAction(BulkActionRequest $request): JsonResponse
    {
        /** @var array{ids: array<int, string|int>, action: string} $validated */
        $validated = $request->validated();

        $count = $this->roleRepository->bulk(
            $validated['ids'],
            $validated['action'],
        );

        $action = $validated['action'];

        return new JsonDataResponse(
            data: ['count' => $count],
            message: "Roles {$action} successfully"
        );
    }
}
