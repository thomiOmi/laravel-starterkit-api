<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\User\Actions\BulkRestoreUsersAction;
use Symfony\Component\HttpFoundation\Response;

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
    #[ScrambleResponse(status: 200, description: 'Users restored successfully')]
    public function __invoke(BulkActionRequest $request): JsonResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkRestoreUsers->handle($validated['ids']);

        return new JsonResponse(
            [
                'status' => Response::HTTP_OK,
                'message' => __('messages.restored', ['resource' => 'Users']),
                'data' => ['count' => $count],
            ],
            Response::HTTP_OK,
        );
    }
}
