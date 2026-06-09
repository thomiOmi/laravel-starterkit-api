<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\User\Actions\StoreUserAction;
use Modules\User\Requests\V1\UserRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group User Management
 *
 * @authenticated
 */
final readonly class StoreController
{
    public function __construct(
        private StoreUserAction $storeUser,
    ) {}

    /**
     * Store a newly created user in storage.
     *
     * @param  UserRequest  $request  The validated user creation request.
     * @return JsonDataResponse The API response containing the new user.
     */
    public function __invoke(UserRequest $request): JsonDataResponse
    {
        $user = $this->storeUser->handle($request->payload());

        return new JsonDataResponse(
            data: new UserResource($user),
            status: Response::HTTP_CREATED,
            message: __('messages.created', ['resource' => 'User'])
        );
    }
}
