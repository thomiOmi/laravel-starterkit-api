<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\BulkRestoreRolesAction;

/**
 * @group Role Management
 *
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
