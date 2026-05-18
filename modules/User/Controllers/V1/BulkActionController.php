<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\JsonDataResponse;
use Modules\User\Actions\BulkDeleteUsersAction;
use Modules\User\Actions\BulkRestoreUsersAction;

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
    public function __invoke(BulkActionRequest $request): JsonDataResponse
    {
        /** @var array{ids: array<int, string|int>, action: string} $validated */
        $validated = $request->validated();

        $count = match ($validated['action']) {
            'delete' => $this->bulkDeleteUsers->handle($validated['ids']),
            'restore' => $this->bulkRestoreUsers->handle($validated['ids']),
            default => 0,
        };

        $action = $validated['action'];

        return new JsonDataResponse(
            data: ['count' => $count],
            message: "Users {$action} successfully"
        );
    }
}
