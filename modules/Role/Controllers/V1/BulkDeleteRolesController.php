<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\SuccessResponse;
use Modules\Role\Actions\BulkDeleteRolesAction;

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
    public function __invoke(BulkActionRequest $request): SuccessResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkDeleteRoles->handle($validated['ids']);

        return new SuccessResponse(
            'OK',
            __('general.deleted', ['resource' => 'Roles']),
            ['count' => $count],
        );
    }
}
