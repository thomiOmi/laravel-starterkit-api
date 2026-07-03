<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\BulkRestoreRolesAction;

final readonly class RoleBulkRestoreController
{
    public function __construct(
        private BulkRestoreRolesAction $bulkRestoreRoles,
    ) {}

    /**
     * Perform bulk restore on roles.
     *
     * @return SuccessResponse<array{count: int}>
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
