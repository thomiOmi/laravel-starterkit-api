<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Http\Resources\UserResource;
use Modules\IAM\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class UserShowController extends Controller
{
    /**
     * Display the specified user.
     *
     * @return SuccessResponse<UserResource>
     */
    public function __invoke(#[CurrentUser] User $currentUser, User $user): SuccessResponse
    {
        if (! $currentUser->can('view', $user)) {
            throw new AccessDeniedHttpException(
                __('general.action_forbidden')
            );
        }

        $user->load(['roles:id,name,guard_name', 'permissions:id,name']);

        return new SuccessResponse(
            data: new UserResource($user),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'User']),
        );
    }
}
