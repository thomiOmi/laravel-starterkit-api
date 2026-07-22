<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\UserResource;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class UserShowController
{
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

        $user->load('roles', 'permissions');

        return new SuccessResponse(
            data: new UserResource($user),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'User']),
        );
    }
}
