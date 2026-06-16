<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Requests\V1\UserRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class CreateController
{
    public function __construct(
        private CreateUserAction $createUser,
    ) {}

    /**
     * Store a newly created user in storage.
     *
     * @param  UserRequest  $request  The validated user creation request.
     * @return JsonResponse The API response containing the new user.
     */
    #[Endpoint(operationId: 'createUser', title: 'Create User')]
    #[ScrambleResponse(status: 201, description: 'User created successfully', examples: ['status' => 201, 'message' => 'User created.', 'data' => ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@example.com']])]
    public function __invoke(UserRequest $request): JsonResponse
    {
        $user = $this->createUser->handle($request->payload());

        return new JsonResponse(
            [
                'status' => Response::HTTP_CREATED,
                'message' => __('messages.created', ['resource' => 'User']),
                'data' => new UserResource($user),
            ],
            Response::HTTP_CREATED,
        );
    }
}
