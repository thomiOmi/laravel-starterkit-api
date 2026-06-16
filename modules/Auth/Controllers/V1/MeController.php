<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\GetAuthenticatedUserAction;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

#[Group('Auth')]
/**
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
    #[Endpoint(operationId: 'me', title: 'Get Authenticated User')]
    #[ScrambleResponse(status: 200, description: 'User profile retrieved', examples: ['status' => 200, 'message' => 'User profile retrieved.', 'data' => ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@example.com']])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $profile = $this->getAuthenticatedUser->handle($user->id);

        if (! $profile) {
            return new JsonResponse(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => __('general.not_found', ['resource' => 'User profile']),
                    'data' => null,
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            [
                'status' => Response::HTTP_OK,
                'message' => __('general.retrieved', ['resource' => 'User profile']),
                'data' => new UserResource($profile),
            ],
            Response::HTTP_OK,
        );
    }
}
