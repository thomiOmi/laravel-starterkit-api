<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Modules\User\Actions\AssignRolesToUserAction;
use Modules\User\Repositories\UserRepository;
use Modules\User\Requests\V1\AssignRolesRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class AssignRolesController
{
    public function __construct(
        private AssignRolesToUserAction $assignRoles,
        private UserRepository $userRepository,
    ) {}

    public function __invoke(string $user, AssignRolesRequest $formRequest): SuccessResponse|ProblemResponse
    {
        /** @var (\Illuminate\Contracts\Auth\Authenticatable&\Modules\User\Models\User)|null $currentUser */
        $currentUser = $formRequest->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: Response::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        if (! $currentUser->can('user.edit')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        $userModel = $this->userRepository->findById($user);

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
