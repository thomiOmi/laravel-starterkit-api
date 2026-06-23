<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\SuccessResponse;
use Modules\Role\Actions\BulkRestoreRolesAction;

final readonly class BulkRestoreRolesController
{
    public function __construct(
        private BulkRestoreRolesAction $bulkRestoreRoles,
    ) {}

    /**
     * Perform bulk restore on roles.
     */
    public function __invoke(BulkActionRequest $request): SuccessResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkRestoreRoles->handle($validated['ids']);

        return new SuccessResponse(
            'OK',
            __('general.restored', ['resource' => 'Roles']),
            ['count' => $count],
        );
    }
}
