<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\UpdateUserAction;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\UserRequest;
use Modules\IAM\Resources\UserResource;

final readonly class UserUpdateController extends Controller
{
    public function __construct(
        private UpdateUserAction $updateUser,
    ) {}

    /**
     * Update the specified user in storage.
     *
     * @return SuccessResponse<UserResource>
     */
    public function __invoke(UserRequest $request, User $user): SuccessResponse
    {
        $userModel = $this->updateUser->handle($user, $request->payload());
        $userModel->load('roles', 'permissions');

        return new SuccessResponse(
            data: new UserResource($userModel),
            title: 'OK',
            detail: __('general.resource_updated', ['resource' => 'User']),
        );
    }
}
