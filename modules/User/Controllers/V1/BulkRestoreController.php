<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\User\Actions\BulkRestoreUsersAction;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class BulkRestoreController
{
    public function __construct(
        private BulkRestoreUsersAction $bulkRestoreUsers,
    ) {}

    /**
     * Perform bulk restore on users.
     */
    #[Endpoint(operationId: 'bulkRestoreUsers', title: 'Bulk Restore Users')]
    #[Response(status: 200, description: 'Users restored successfully', examples: ['status' => 200, 'message' => 'Users restored.', 'data' => null])]
    public function __invoke(BulkActionRequest $request): JsonDataResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkRestoreUsers->handle($validated['ids']);

        return new JsonDataResponse(
            data: ['count' => $count],
            message: __('messages.bulk_action', [
                'resource' => 'Users',
                'action' => 'restore',
            ])
        );
    }
}
