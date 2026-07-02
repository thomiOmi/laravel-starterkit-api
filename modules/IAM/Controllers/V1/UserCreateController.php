<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
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
     */
    public function __invoke(UserRequest $request): SuccessResponse
    {
        $user = $this->createUser->handle($request->payload());

        return new SuccessResponse(
            'Created',
            __('general.created', ['resource' => 'User']),
            new UserResource($user),
            Response::HTTP_CREATED,
        );
    }
}
