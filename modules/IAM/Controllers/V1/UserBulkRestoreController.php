<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\BulkRestoreUsersAction;

final readonly class UserBulkRestoreController
{
    public function __construct(
        private BulkRestoreUsersAction $bulkRestoreUsers,
    ) {}

    /**
     * Perform bulk restore on users.
     */
    public function __invoke(BulkActionRequest $request): SuccessResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkRestoreUsers->handle($validated['ids']);

        return new SuccessResponse(
            'OK',
            __('general.restored', ['resource' => 'Users']),
            ['count' => $count],
        );
    }
}
