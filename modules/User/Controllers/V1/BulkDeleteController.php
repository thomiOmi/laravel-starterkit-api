<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\User\Actions\BulkDeleteUsersAction;
use Symfony\Component\HttpFoundation\Response;

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
    #[ScrambleResponse(status: 200, description: 'Users deleted successfully')]
    public function __invoke(BulkActionRequest $request): JsonResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkDeleteUsers->handle($validated['ids']);

        return new JsonResponse(
            [
                'status' => Response::HTTP_OK,
                'message' => __('general.deleted', ['resource' => 'Users']),
                'data' => ['count' => $count],
            ],
            Response::HTTP_OK,
        );
    }
}
