<?php

declare(strict_types=1);

namespace Modules\Role\Controllers;

use App\DTOs\DataTableDTO;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Role\Actions\CreateRoleAction;
use Modules\Role\Actions\UpdateRoleAction;
use Modules\Role\DTOs\RoleDTO;
use Modules\Role\Repositories\RoleRepository;
use Modules\Role\Requests\RoleRequest;
use Modules\Role\Resources\RoleResource;

class RoleController extends Controller
{
    /**
     * Create a new RoleController instance.
     */
    public function __construct(protected RoleRepository $repository) {}

    /**
     * Display a listing of the roles.
     */
    public function index(Request $request): JsonResponse
    {
        $dto = DataTableDTO::fromRequest($request);
        $roles = $this->repository->getDataTable($dto, relations: ['permissions']);

        return $this->paginateResponse($roles, RoleResource::class, 'Roles retrieved successfully');
    }

    /**
     * Store a newly created role.
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
     */
    public function update(RoleRequest $request, string|int $id, UpdateRoleAction $action): JsonResponse
    {
        $dto = RoleDTO::fromRequest($request);
        $action->execute($id, $dto);

        $role = $this->repository->findById($id, relations: ['permissions']);

        return $this->successResponse(
            new RoleResource($role),
            'Role updated successfully'
        );
    }

    /**
     * Remove the specified role.
     */
    public function destroy(string|int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->successResponse(null, 'Role deleted successfully');
    }

    /**
     * Perform bulk action on roles.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:roles,id'],
            'action' => ['required', 'string', 'in:delete,update,restore,forceDelete'],
            'data' => ['nullable', 'array'],
        ]);

        $count = $this->repository->bulk(
            $request->input('ids'),
            $request->input('action'),
            $request->input('data', [])
        );

        return $this->successResponse(
            ['count' => $count],
            "Roles {$request->input('action')} successfully"
        );
    }
}
