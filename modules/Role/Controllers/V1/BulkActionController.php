<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\BulkDeleteRolesAction;
use Modules\Role\Actions\BulkRestoreRolesAction;
use Modules\User\Models\User;

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

        /** @var User $user */
        $user = $request->user();

        $count = match ($validated['action']) {
            'delete' => $user->can('role.delete') ? $this->bulkDeleteRoles->handle($validated['ids']) : 0,
            'restore' => $user->can('role.restore') ? $this->bulkRestoreRoles->handle($validated['ids']) : 0,
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
