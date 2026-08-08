<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\AssignRolesToUserAction;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\AssignRolesRequest;
use Modules\IAM\Resources\UserResource;

final readonly class UserAssignRolesController extends Controller
{
    public function __construct(
        private AssignRolesToUserAction $assignRoles,
    ) {}

    /**
     * @return SuccessResponse<UserResource>
     */
    public function __invoke(AssignRolesRequest $request, User $user): SuccessResponse
    {
        /** @var array<int, string> $roles */
        $roles = $request->validated('roles');

        $userModel = $this->assignRoles->handle($user, $roles);
        $userModel->load('roles', 'permissions');

        return new SuccessResponse(
            data: new UserResource($userModel),
            title: 'OK',
            detail: __('general.roles_assigned'),
        );
    }
}
