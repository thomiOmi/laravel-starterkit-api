<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Requests\V1\UserRequest;
use Modules\User\Resources\UserResource;

/**
 * @authenticated
 */
final readonly class UpdateController
{
    public function __construct(
        private UpdateUserAction $updateUser,
    ) {}

    /**
     * Update the specified user in storage.
     *
     * @param  UserRequest  $request  The validated user update request.
     * @param  string  $user  The user ID.
     */
    public function __invoke(UserRequest $request, string $user): SuccessResponse|ProblemResponse
    {
        $user = $this->updateUser->handle($user, $request->payload());

        if (! $user) {
            return new ProblemResponse(
                title: 'Not Found',
                status: 404,
                detail: __('general.not_found', ['resource' => 'User']),
            );
        }

        return new SuccessResponse(
            'OK',
            __('general.updated', ['resource' => 'User']),
            new UserResource($user),
        );
    }
}
