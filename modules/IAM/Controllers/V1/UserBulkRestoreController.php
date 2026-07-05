<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Actions\BulkRestoreUsersAction;

final readonly class UserBulkRestoreController
{
    public function __construct(
        private BulkRestoreUsersAction $bulkRestoreUsers,
    ) {}

    /**
     * Perform bulk restore on users.
     *
     * @return SuccessResponse<array{count: int}>
     *
     * @throws AuthenticationException Full authentication is required to access user management.
     * @throws AuthorizationException You do not have permission to restore users.
     * @throws ValidationException The submitted data failed validation rules.
     */
    public function __invoke(BulkActionRequest $request): SuccessResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkRestoreUsers->handle($validated['ids']);

        return new SuccessResponse(
            data: ['count' => $count],
            title: 'OK',
            detail: __('general.restored', ['resource' => 'Users']),
        );
    }
}
