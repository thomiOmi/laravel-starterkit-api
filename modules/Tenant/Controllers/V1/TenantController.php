<?php

declare(strict_types=1);

namespace Modules\Tenant\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Actions\CreateTenantAction;
use Modules\Tenant\DTOs\TenantDTO;
use Modules\Tenant\Repositories\TenantRepository;
use Modules\Tenant\Resources\TenantResource;

/**
 * @tags Tenant
 */
class TenantController extends Controller
{
    public function __construct(protected TenantRepository $repository) {}

    /**
     * List Tenants
     *
     * Get all tenants.
     */
    public function index(Request $request): JsonResponse
    {
        $tenants = $this->repository->all(['*'], ['domains']);

        return $this->successResponse(
            TenantResource::collection($tenants),
            'Tenants retrieved successfully.'
        );
    }

    /**
     * Store Tenant
     *
     * Create a new tenant.
     */
    public function store(Request $request, CreateTenantAction $action): JsonResponse
    {
        $request->validate([
            'id' => 'required|string|unique:tenants,id',
            'domain' => 'required|string|unique:domains,domain',
        ]);

        $dto = TenantDTO::fromRequest($request);
        $tenant = $action->execute($dto);

        return $this->successResponse(
            new TenantResource($tenant->load('domains')),
            'Tenant created successfully.',
            201
        );
    }

    /**
     * Show Tenant
     *
     * Get a tenant by ID.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = $this->repository->findById($id, ['*'], ['domains']);

        return $this->successResponse(
            new TenantResource($tenant),
            'Tenant retrieved successfully.'
        );
    }

    /**
     * Delete Tenant
     *
     * Delete a tenant.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->successResponse(null, 'Tenant deleted successfully.');
    }
}
