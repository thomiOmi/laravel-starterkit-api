<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Role\Actions\ShowRoleAction;
use Modules\Role\Resources\RoleResource;
use Modules\User\Models\User;

/**
 * @authenticated
 */
final readonly class ShowController
{
    public function __construct(
        private ShowRoleAction $showRole
    ) {}

    /**
     * Display the specified role.
     */
    public function __invoke(string $role): SuccessResponse|ProblemResponse
    {
        /** @var Authenticatable&User $user */
        $user = auth()->user();

        if (! $user->can('role.view')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: 403,
                detail: __('general.forbidden'),
            );
        }

        $role = $this->showRole->handle($role);

        if ($role === null) {
            return new ProblemResponse(
                title: 'Not Found',
                status: 404,
                detail: __('general.not_found', ['resource' => 'Role']),
            );
        }

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Role']),
            new RoleResource($role),
        );
    }
}
