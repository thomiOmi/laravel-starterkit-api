<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\DataResponse;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Models\User;
use Modules\User\Requests\V1\UserRequest;
use Modules\User\Resources\UserResource;

/**
 * @tags User
 */
final readonly class UpdateController
{
    public function __construct(
        private UpdateUserAction $updateUser,
    ) {}

    /**
     * Update the specified user in storage.
     */
    public function __invoke(UserRequest $request, User $user): DataResponse
    {
        $updatedUser = $this->updateUser->handle($user, $request->payload());

        return new DataResponse(
            data: new UserResource($updatedUser),
            message: __('messages.updated', ['resource' => 'User'])
        );
    }
}
