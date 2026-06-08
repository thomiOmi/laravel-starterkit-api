<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\DataResponse;
use Modules\User\Actions\BulkDeleteUsersAction;
use Modules\User\Actions\BulkRestoreUsersAction;
use Modules\User\Models\User;

/**
 * @tags User
 */
final readonly class BulkActionController
{
    public function __construct(
        private BulkDeleteUsersAction $bulkDeleteUsers,
        private BulkRestoreUsersAction $bulkRestoreUsers,
    ) {}

    /**
     * Perform bulk action on users.
     */
    public function __invoke(BulkActionRequest $request): DataResponse
    {
        /** @var array{ids: array<int, string|int>, action: string} $validated */
        $validated = $request->validated();

        /** @var User $user */
        $user = $request->user();

        $count = match ($validated['action']) {
            'delete' => $user->can('user.delete') ? $this->bulkDeleteUsers->handle($validated['ids']) : 0,
            'restore' => $user->can('user.restore') ? $this->bulkRestoreUsers->handle($validated['ids']) : 0,
            default => 0,
        };

        $action = $validated['action'];

        return new DataResponse(
            data: ['count' => $count],
            message: __('messages.bulk_action', [
                'resource' => 'Users',
                'action' => $action,
            ])
        );
    }
}
