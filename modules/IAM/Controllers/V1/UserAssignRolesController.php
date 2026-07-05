<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\IAM\Actions\AssignRolesToUserAction;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\AssignRolesRequest;
use Modules\IAM\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class UserAssignRolesController
{
    public function __construct(
        private AssignRolesToUserAction $assignRoles,
    ) {}

    /**
     * @return SuccessResponse<UserResource>|ProblemResponse
     */
    public function __invoke(string $id, AssignRolesRequest $formRequest): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $formRequest->user();

        $userModel = User::query()->find($id);

        if (! $userModel) {
            return new ProblemResponse(
                title: 'Not Found',
                status: Response::HTTP_NOT_FOUND,
                detail: __('general.not_found', ['resource' => 'User']),
            );
        }

        /** @var array<int, string> $roles */
        $roles = $formRequest->validated('roles');

        $userModel = $this->assignRoles->handle($userModel, $roles);

        $userModel->load('roles');

        return new SuccessResponse(
            data: new UserResource($userModel),
            title: 'OK',
            detail: __('general.roles_assigned'),
        );
    }
}
