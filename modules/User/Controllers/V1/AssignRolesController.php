<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Modules\User\Actions\AssignRolesToUserAction;
use Modules\User\Requests\V1\AssignRolesRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class AssignRolesController
{
    public function __construct(
        private AssignRolesToUserAction $assignRoles,
    ) {}

    /**
     * Assign roles to the specified user.
     *
     * @param  AssignRolesRequest  $request  The validated assign roles request.
     * @param  string  $user  The user ID.
     */
    public function __invoke(AssignRolesRequest $request, string $user): SuccessResponse|ProblemResponse
    {
        /** @var array<int, string> $roles */
        $roles = $request->validated('roles');

        $userModel = $this->assignRoles->handle($user, $roles);

        if (! $userModel) {
            return new ProblemResponse(
                title: 'Not Found',
                status: Response::HTTP_NOT_FOUND,
                detail: __('general.not_found', ['resource' => 'User']),
            );
        }

        return new SuccessResponse(
            'OK',
            __('general.roles_assigned'),
            new UserResource($userModel),
        );
    }
}
