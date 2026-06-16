<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\User\Actions\BulkDeleteUsersAction;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class BulkDeleteController
{
    public function __construct(
        private BulkDeleteUsersAction $bulkDeleteUsers,
    ) {}

    /**
     * Perform bulk delete on users.
     */
    #[Endpoint(operationId: 'bulkDeleteUsers', title: 'Bulk Delete Users')]
    #[Response(status: 204, description: 'Users deleted successfully')]
    public function __invoke(BulkActionRequest $request): JsonDataResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkDeleteUsers->handle($validated['ids']);

        return new JsonDataResponse(
            data: ['count' => $count],
            message: __('messages.bulk_action', [
                'resource' => 'Users',
                'action' => 'delete',
            ])
        );
    }
}
