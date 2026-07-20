<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\ShowUserAction;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\UserResource;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class UserShowController
{
    public function __construct(
        private ShowUserAction $showUser
    ) {}

    /**
     * Display the specified user.
     *
     * @return SuccessResponse<UserResource>
     */
    public function __invoke(#[CurrentUser] User $currentUser, User $user): SuccessResponse
    {
        if (! $currentUser->is($user) && ! $currentUser->can(PermissionEnum::UserView->value)) {
            throw new AccessDeniedHttpException(
                __('general.action_forbidden')
            );
        }

        $userModel = $this->showUser->handle($user);

        return new SuccessResponse(
            data: new UserResource($userModel),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'User']),
        );
    }
}
