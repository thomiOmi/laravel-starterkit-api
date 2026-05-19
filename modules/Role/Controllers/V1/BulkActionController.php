<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\BulkDeleteRolesAction;
use Modules\Role\Actions\BulkRestoreRolesAction;

/**
 * @tags Role
 */
final readonly class BulkActionController
{
    public function __construct(
        private BulkDeleteRolesAction $bulkDeleteRoles,
        private BulkRestoreRolesAction $bulkRestoreRoles,
    ) {}

    /**
     * Perform bulk action on roles.
     */
    public function __invoke(BulkActionRequest $request): JsonDataResponse
    {
        /** @var array{ids: array<int, string|int>, action: string} $validated */
        $validated = $request->validated();

        $count = match ($validated['action']) {
            'delete' => $this->bulkDeleteRoles->handle($validated['ids']),
            'restore' => $this->bulkRestoreRoles->handle($validated['ids']),
            default => 0,
        };

        $action = $validated['action'];

        return new JsonDataResponse(
            data: ['count' => $count],
            message: __('messages.bulk_action', [
                'resource' => 'Roles',
                'action' => $action,
            ])
        );
    }
}
