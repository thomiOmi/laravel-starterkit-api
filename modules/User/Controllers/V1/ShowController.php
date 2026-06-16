<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
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
    public function __invoke(User $user): JsonDataResponse
    {
        $user = $this->showUser->handle($user->id);

        if (! $user) {
            return new JsonDataResponse(
                data: null,
                status: Response::HTTP_NOT_FOUND,
                message: __('messages.not_found', ['resource' => 'User'])
            );
        }

        return new JsonDataResponse(
            data: new UserResource($user),
            message: __('messages.retrieved', ['resource' => 'User'])
        );
    }
}
