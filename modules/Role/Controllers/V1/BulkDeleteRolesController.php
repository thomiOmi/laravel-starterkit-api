<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\BulkDeleteRolesAction;
use Symfony\Component\HttpFoundation\Response;

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
    #[ScrambleResponse(status: 200, description: 'Roles deleted successfully')]
    public function __invoke(BulkActionRequest $request): JsonResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkDeleteRoles->handle($validated['ids']);

        return new JsonResponse(
            [
                'status' => Response::HTTP_OK,
                'message' => __('general.deleted', ['resource' => 'Roles']),
                'data' => ['count' => $count],
            ],
            Response::HTTP_OK,
        );
    }
}
