<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Role\Actions\ListPermissionsAction;
use Modules\Role\Filters\PermissionFilter;
use Modules\Role\Resources\PermissionResource;
use Modules\User\Models\User;

/**
 * @authenticated
 */
final readonly class PermissionIndexController
{
    public function __construct(
        private ListPermissionsAction $listPermissions
    ) {}

    /**
     * Display a paginated listing of permissions.
     */
    public function __invoke(Request $request, PermissionFilter $filter): SuccessResponse|ProblemResponse
    {
        /** @var Authenticatable&User $user */
        $user = auth()->user();

        if (! $user->can('permission.view')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: 403,
                detail: __('general.forbidden'),
            );
        }

        $permissions = $this->listPermissions->handle(
            $filter,
            $request->integer('page.size', 20),
            $request->integer('page.number', 1),
        );

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Permissions']),
            PermissionResource::collection($permissions),
            200,
        );
    }
}
