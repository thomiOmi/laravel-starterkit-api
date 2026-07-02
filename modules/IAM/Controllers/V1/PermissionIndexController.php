<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Actions\ListPermissionsAction;
use Modules\IAM\Filters\PermissionFilter;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class PermissionIndexController
{
    public function __construct(
        private ListPermissionsAction $listPermissions
    ) {}

    /**
     * Display a paginated listing of permissions.
     *
     * @response SuccessResponse<AnonymousResourceCollection>
     */
    public function __invoke(Request $request, PermissionFilter $filter): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: Response::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        if (! $currentUser->can('permission.view')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
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
        );
    }
}
