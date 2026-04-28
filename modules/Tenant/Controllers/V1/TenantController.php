<?php

declare(strict_types=1);

namespace Modules\Tenant\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\DTOs\TenantDTO;
use Modules\Tenant\Repositories\TenantRepository;
use Modules\Tenant\Resources\TenantResource;

class TenantController extends Controller
{
    public function __construct(protected TenantRepository $repository) {}

    public function index(Request $request): JsonResponse
    {
        $tenants = $this->repository->all(['*'], ['domains']);

        return $this->successResponse(
            TenantResource::collection($tenants),
            'Tenants retrieved successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|string|unique:tenants,id',
            'domain' => 'required|string|unique:domains,domain',
        ]);

        $dto = TenantDTO::fromRequest($request);

        $tenant = $this->repository->create(['id' => $dto->id]);
        $tenant->createDomain(['domain' => $dto->domain]);

        return $this->successResponse(
            new TenantResource($tenant->load('domains')),
            'Tenant created successfully.',
            201
        );
    }

    public function show(string $id): JsonResponse
    {
        $tenant = $this->repository->findById($id, ['*'], ['domains']);

        return $this->successResponse(
            new TenantResource($tenant),
            'Tenant retrieved successfully.'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->successResponse(null, 'Tenant deleted successfully.');
    }
}
