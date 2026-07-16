<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Requests\PaginationRequest;
use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Actions\ListPermissionsAction;
use Modules\IAM\Filters\PermissionFilter;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class PermissionListController
{
    public function __construct(
        private ListPermissionsAction $listPermissions
    ) {}

    /**
     * Display a paginated listing of permissions.
     *
     * @return SuccessResponse<AnonymousResourceCollection>|ProblemResponse
     */
    public function __invoke(PaginationRequest $request, PermissionFilter $filter): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        if (! $currentUser->can(PermissionEnum::PermissionView->value)) {
            return new ProblemResponse(
                title: __('auth.http_forbidden'),
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.action_forbidden'),
            );
        }

        $permissions = $this->listPermissions->handle(
            $filter,
            $request->integer('page.size', 20),
            $request->integer('page.number', 1),
        );

        return new SuccessResponse(
            data: PermissionResource::collection($permissions),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Permissions']),
        );
    }
}
