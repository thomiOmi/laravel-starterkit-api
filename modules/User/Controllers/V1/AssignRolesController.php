<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\IAM\Models\User;
use Modules\User\Actions\AssignRolesToUserAction;
use Modules\User\Repositories\UserRepository;
use Modules\User\Requests\V1\AssignRolesRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class AssignRolesController
{
    public function __construct(
        private AssignRolesToUserAction $assignRoles,
        private UserRepository $repository,
    ) {}

    public function __invoke(string $id, AssignRolesRequest $formRequest): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $formRequest->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: Response::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        $userModel = $this->repository->findById($id);

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
