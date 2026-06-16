<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Modules\User\Actions\ShowUserAction;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class ShowController
{
    public function __construct(
        private ShowUserAction $showUser
    ) {}

    /**
     * Display the specified user.
     */
    #[Endpoint(operationId: 'showUser', title: 'Show User')]
    #[ScrambleResponse(status: 200, description: 'User details retrieved', examples: ['status' => 200, 'message' => 'User retrieved.', 'data' => ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@example.com', 'roles' => [], 'permissions' => []]])]
    public function __invoke(User $user): JsonResponse
    {
        $user = $this->showUser->handle($user->id);

        if (! $user) {
            return new JsonResponse(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => __('messages.not_found', ['resource' => 'User']),
                    'data' => null,
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'status' => Response::HTTP_OK,
                'message' => __('messages.retrieved', ['resource' => 'User']),
                'data' => new UserResource($user),
            ],
            Response::HTTP_OK,
        );
    }
}
