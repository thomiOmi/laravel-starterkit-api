<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\JsonDataResponse;
use Illuminate\Database\DatabaseManager;
use Modules\Role\Models\Role;

/**
 * @tags Role
 */
final readonly class BulkActionController
{
    public function __construct(
        private DatabaseManager $database
    ) {}

    /**
     * Perform bulk action on roles.
     */
    public function __invoke(BulkActionRequest $request): JsonDataResponse
    {
        /** @var array{ids: array<int, string|int>, action: string} $validated */
        $validated = $request->validated();

        $count = $this->database->transaction(function () use ($validated) {
            $query = Role::whereIn('id', $validated['ids']);

            return match ($validated['action']) {
                'delete' => $query->delete(),
                'restore' => $query->restore(),
                default => 0,
            };
        });

        $action = $validated['action'];

        return new JsonDataResponse(
            data: ['count' => $count],
            message: __('messages.bulk_action', [
                'resource' => 'Roles',
                'action' => $action,
            ])
        );
    }
}
