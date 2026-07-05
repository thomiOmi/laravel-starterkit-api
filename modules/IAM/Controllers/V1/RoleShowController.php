<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Modules\IAM\Actions\ShowRoleAction;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class RoleShowController
{
    public function __construct(
        private ShowRoleAction $showRole
    ) {}

    /**
     * Display the specified role.
     *
     * @param  string  $id  The role ID.
     * @return SuccessResponse<RoleResource>|ProblemResponse
     *
     * @throws AuthenticationException Full authentication is required to access role management.
     * @throws AuthorizationException You do not have permission to view roles.
     * @throws ModelNotFoundException The specified role was not found.
     */
    public function __invoke(Request $request, string $id): SuccessResponse|ProblemResponse
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

        $role = $this->showRole->handle($id);

        if ($role === null) {
            return new ProblemResponse(
                title: 'Not Found',
                status: Response::HTTP_NOT_FOUND,
                detail: __('general.not_found', ['resource' => 'Role']),
            );
        }

        return new SuccessResponse(
            data: new RoleResource($role),
            title: 'OK',
            detail: __('general.retrieved', ['resource' => 'Role']),
        );
    }
}
