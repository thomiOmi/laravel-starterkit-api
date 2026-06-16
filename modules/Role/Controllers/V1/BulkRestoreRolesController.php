<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\Role\Actions\BulkRestoreRolesAction;

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
    #[Response(status: 200, description: 'Roles restored successfully', examples: ['status' => 200, 'message' => 'Roles restored.', 'data' => null])]
    public function __invoke(BulkActionRequest $request): JsonDataResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkRestoreRoles->handle($validated['ids']);

        return new JsonDataResponse(
            data: ['count' => $count],
            message: __('messages.bulk_action', [
                'resource' => 'Roles',
                'action' => 'restore',
            ])
        );
    }
}
