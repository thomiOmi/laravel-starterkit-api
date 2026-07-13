<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\IAM\Actions\DeleteUserAction;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class UserDeleteController
{
    public function __construct(
        private DeleteUserAction $deleteUser,
    ) {}

    /**
     * Remove the specified user from storage.
     *
     * @param  string  $user  The user ID.
     * @return SuccessResponse<null>|ProblemResponse
     */
    public function __invoke(Request $request, string $user): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        $currentUserId = $currentUser->id;

        if ($currentUserId === $user) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.self_delete_forbidden'),
            );
        }

        if (! $currentUser->can(PermissionEnum::UserDelete->value)) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        $targetUser = User::query()->findOrFail($user);

        if ($targetUser->hasRole(RoleEnum::SuperAdmin->value) && ! $currentUser->hasRole(RoleEnum::SuperAdmin->value)) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        if ($this->deleteUser->handle($user)) {
            return new SuccessResponse(null, status: Response::HTTP_NO_CONTENT);
        }

        return new ProblemResponse(
            title: 'Forbidden',
            status: Response::HTTP_FORBIDDEN,
            detail: __('general.delete_error', ['resource' => 'User']),
        );
    }
}
