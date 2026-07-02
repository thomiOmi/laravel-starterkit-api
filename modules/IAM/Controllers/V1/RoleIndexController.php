<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Actions\ListRolesAction;
use Modules\IAM\Filters\RoleFilter;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class RoleIndexController
{
    public function __construct(
        private ListRolesAction $listRoles
    ) {}

    /**
     * Display a paginated listing of the roles.
     */
    /**
     * @response SuccessResponse<AnonymousResourceCollection>
     */
    public function __invoke(Request $request, RoleFilter $filter): SuccessResponse|ProblemResponse
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

        if (! $currentUser->can('role.view')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        $roles = $this->listRoles->handle(
            $filter,
            $request->integer('page.size', 10),
            $request->integer('page.number', 1),
        );

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Roles']),
            RoleResource::collection($roles),
        );
    }
}
