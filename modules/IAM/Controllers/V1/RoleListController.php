<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Requests\PaginationRequest;
use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Actions\ListRolesAction;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class RoleListController
{
    public function __construct(
        private ListRolesAction $listRoles
    ) {}

    /**
     * Display a paginated listing of the roles.
     *
     * @return SuccessResponse<AnonymousResourceCollection>|ProblemResponse
     */
    public function __invoke(PaginationRequest $request): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        if (! $currentUser->can(PermissionEnum::RoleView->value)) {
            return new ProblemResponse(
                title: __('auth.http_forbidden'),
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.action_forbidden'),
            );
        }

        $roles = $this->listRoles->handle(
            $request->integer('page.size', 10),
            $request->integer('page.number', 1),
        );

        return new SuccessResponse(
            data: RoleResource::collection($roles),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Roles']),
        );
    }
}
