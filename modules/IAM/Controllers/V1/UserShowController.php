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
     * @param  string  $id  The user ID.
     * @return SuccessResponse<UserResource>|ProblemResponse
     */
    public function __invoke(Request $request, string $id): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        $currentUserId = (string) $currentUser->getKey();

        if ($currentUserId !== $id && ! $currentUser->can(PermissionEnum::UserView->value)) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        $user = $this->showUser->handle($id);

        return new SuccessResponse(
            data: new UserResource($user),
            title: 'OK',
            detail: __('general.retrieved', ['resource' => 'User']),
        );
    }
}
