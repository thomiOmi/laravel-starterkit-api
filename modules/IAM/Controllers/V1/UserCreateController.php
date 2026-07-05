<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Actions\CreateUserAction;
use Modules\IAM\Requests\V1\UserRequest;
use Modules\IAM\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class UserCreateController
{
    public function __construct(
        private CreateUserAction $createUser,
    ) {}

    /**
     * Store a newly created user.
     *
     * @param  UserRequest  $request  The validated user creation request.
     * @return SuccessResponse<UserResource>
     *
     * @throws AuthenticationException Full authentication is required to access user management.
     * @throws AuthorizationException You do not have permission to create users.
     * @throws ValidationException The submitted data failed validation rules.
     */
    public function __invoke(UserRequest $request): SuccessResponse
    {
        $user = $this->createUser->handle($request->payload());

        return new SuccessResponse(
            data: new UserResource($user),
            title: 'Created',
            detail: __('general.created', ['resource' => 'User']),
            status: Response::HTTP_CREATED,
        );
    }
}
