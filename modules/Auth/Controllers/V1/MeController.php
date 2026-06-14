<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\GetAuthenticatedUserAction;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Auth
 *
 * @authenticated
 */
final readonly class MeController
{
    public function __construct(
        private GetAuthenticatedUserAction $getAuthenticatedUser
    ) {}

    /**
     * Get the authenticated user profile.
     */
    public function __invoke(Request $request): JsonDataResponse
    {
        /** @var User $user */
        $user = $request->user();

        $profile = $this->getAuthenticatedUser->handle($user->id);

        if (! $profile) {
            return new JsonDataResponse(
                data: null,
                status: Response::HTTP_NOT_FOUND,
                message: __('messages.not_found', ['resource' => 'User profile'])
            );
        }

        return new JsonDataResponse(
            data: new UserResource($profile),
            message: __('messages.retrieved', ['resource' => 'User profile'])
        );
    }
}
