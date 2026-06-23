<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Requests\V1\UserRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class CreateController
{
    public function __construct(
        private CreateUserAction $createUser,
    ) {}

    /**
     * Store a newly created user.
     *
     * @param  UserRequest  $request  The validated user creation request.
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
