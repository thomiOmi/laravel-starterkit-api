<?php

declare(strict_types=1);

namespace Modules\AuditLog\Controllers\V1;

use App\DTOs\DataTableDTO;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AuditLog\Repositories\AuditLogRepository;
use Modules\AuditLog\Resources\AuditLogResource;

/**
 * Class AuditLogController
 *
 * Controller for AuditLog API endpoints.
 */
class AuditLogController extends Controller
{
    use ApiResponser;

    /**
     * AuditLogController constructor.
     */
    public function __construct(
        protected AuditLogRepository $repository
    ) {}

    /**
     * Display a listing of audit logs.
     *
     * @group Audit Log
     */
    public function index(Request $request): JsonResponse
    {
        $dto = DataTableDTO::fromRequest($request);
        $logs = $this->repository->getDataTable($dto);

        return $this->paginateResponse($logs, AuditLogResource::class);
    }

    /**
     * Display the specified audit log.
     *
     * @group Audit Log
     */
    public function show(string $id): JsonResponse
    {
        $log = $this->repository->findById($id);

        return $this->successResponse(new AuditLogResource($log));
    }
}
