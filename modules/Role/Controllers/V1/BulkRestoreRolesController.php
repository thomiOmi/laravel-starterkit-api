<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\BulkRestoreRolesAction;
use Symfony\Component\HttpFoundation\Response;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class BulkRestoreRolesController
{
    public function __construct(
        private BulkRestoreRolesAction $bulkRestoreRoles,
    ) {}

    /**
     * Perform bulk restore on roles.
     */
    #[Endpoint(operationId: 'bulkRestoreRoles', title: 'Bulk Restore Roles')]
    #[ScrambleResponse(status: 200, description: 'Roles restored successfully')]
    public function __invoke(BulkActionRequest $request): JsonResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkRestoreRoles->handle($validated['ids']);

        return new JsonResponse(
            [
                'status' => Response::HTTP_OK,
                'message' => __('messages.restored', ['resource' => 'Roles']),
                'data' => ['count' => $count],
            ],
            Response::HTTP_OK,
        );
    }
}
