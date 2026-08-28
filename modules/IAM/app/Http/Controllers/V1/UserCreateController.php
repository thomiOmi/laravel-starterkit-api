<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\CreateUserAction;
use Modules\IAM\Http\Requests\V1\UserRequest;
use Modules\IAM\Http\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class UserCreateController extends Controller
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
        $user->load(['roles:id,name,guard_name', 'permissions:id,name']);

        return new SuccessResponse(
            data: new UserResource($user),
            title: 'Created',
            detail: __('general.resource_created', ['resource' => 'User']),
            status: Response::HTTP_CREATED,
        );
    }
}
