<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\IAM\Actions\UpdateUserAction;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\UserRequest;
use Modules\IAM\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class UserUpdateController
{
    public function __construct(
        private UpdateUserAction $updateUser,
    ) {}

    /**
     * Update the specified user in storage.
     *
     * @param  UserRequest  $request  The validated user update request.
     * @param  string  $id  The user ID.
     *
     * @response SuccessResponse<UserResource>
     */
    public function __invoke(UserRequest $request, string $id): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: Response::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        $user = $this->updateUser->handle($id, $request->payload());

        if (! $user) {
            return new ProblemResponse(
                title: 'Not Found',
                status: Response::HTTP_NOT_FOUND,
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
