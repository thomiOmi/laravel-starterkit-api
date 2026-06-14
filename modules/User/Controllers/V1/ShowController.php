<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\User\Actions\ShowUserAction;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group User Management
 *
 * @authenticated
 */
final readonly class ShowController
{
    /**
     * Create a new ShowController instance.
     */
    public function __construct(
        private ShowUserAction $showUser
    ) {}

    /**
     * Display the specified user.
     *
     * @param  string  $user  The user ID.
     */
    public function __invoke(string $user): JsonDataResponse
    {
        $userInstance = $this->showUser->handle($user);

        if (! $userInstance) {
            return new JsonDataResponse(
                data: null,
                status: Response::HTTP_NOT_FOUND,
                message: __('messages.not_found', ['resource' => 'User'])
            );
        }

        return new JsonDataResponse(
            data: new UserResource($userInstance),
            message: __('messages.retrieved', ['resource' => 'User'])
        );
    }
}
