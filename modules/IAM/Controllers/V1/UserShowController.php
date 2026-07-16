<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\IAM\Actions\ShowUserAction;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class UserShowController
{
    public function __construct(
        private ShowUserAction $showUser
    ) {}

    /**
     * Display the specified user.
     *
     * @param  string  $user  The user ID.
     * @return SuccessResponse<UserResource>|ProblemResponse
     */
    public function __invoke(Request $request, string $user): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        $currentUserId = $currentUser->id;

        if ($currentUserId !== $user && ! $currentUser->can(PermissionEnum::UserView->value)) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.action_forbidden'),
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
