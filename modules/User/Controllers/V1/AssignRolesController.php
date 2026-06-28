<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Modules\User\Actions\AssignRolesToUserAction;
use Modules\User\Models\User;
use Modules\User\Requests\V1\AssignRolesRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class AssignRolesController
{
    public function __construct(
        private AssignRolesToUserAction $assignRoles,
    ) {}

    public function __invoke(string $user, AssignRolesRequest $formRequest): SuccessResponse|ProblemResponse
    {
        $userModel = User::find($user);

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
            'OK',
            __('general.roles_assigned'),
            new UserResource($userModel),
        );
    }
}
