<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\Role\Actions\BulkDeleteRolesAction;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class BulkDeleteRolesController
{
    public function __construct(
        private BulkDeleteRolesAction $bulkDeleteRoles,
    ) {}

    /**
     * Perform bulk delete on roles.
     */
    #[Endpoint(operationId: 'bulkDeleteRoles', title: 'Bulk Delete Roles')]
    #[Response(status: 204, description: 'Roles deleted successfully')]
    public function __invoke(BulkActionRequest $request): JsonDataResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkDeleteRoles->handle($validated['ids']);

        return new JsonDataResponse(
            data: ['count' => $count],
            message: __('messages.bulk_action', [
                'resource' => 'Roles',
                'action' => 'delete',
            ])
        );
    }
}
