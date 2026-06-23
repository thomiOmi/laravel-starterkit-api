<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\SuccessResponse;
use Modules\User\Actions\BulkDeleteUsersAction;

final readonly class BulkDeleteController
{
    public function __construct(
        private BulkDeleteUsersAction $bulkDeleteUsers,
    ) {}

    /**
     * Perform bulk delete on users.
     */
    public function __invoke(BulkActionRequest $request): SuccessResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkDeleteUsers->handle($validated['ids']);

        return new SuccessResponse(
            'OK',
            __('general.deleted', ['resource' => 'Users']),
            ['count' => $count],
        );
    }
}
