<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\GetAuthenticatedUserAction;
use Modules\User\Resources\UserResource;

final readonly class MeController
{
    public function __construct(
        private GetAuthenticatedUserAction $getAuthenticatedUser
    ) {}

    /**
     * Get the authenticated user profile.
     */
    public function __invoke(Request $request): SuccessResponse
    {
        /** @var \Modules\User\Models\User $user */
        $user = $request->user();

        $profile = $this->getAuthenticatedUser->handle($user);

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'User profile']),
            new UserResource($profile),
        );
    }
}
