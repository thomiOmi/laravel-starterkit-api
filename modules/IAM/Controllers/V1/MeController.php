<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\IAM\Actions\GetAuthenticatedUserAction;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class MeController
{
    public function __construct(
        private GetAuthenticatedUserAction $getAuthenticatedUser
    ) {}

    /**
     * Me.
     *
     * Retrieves the profile information of the currently authenticated user.
     *
     * @return SuccessResponse<UserResource>|ProblemResponse
     *
     * @throws AuthenticationException Full authentication is required.
     * @throws AuthorizationException You do not have permission to access user profile.
     */
    public function __invoke(Request $request): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: Response::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        $profile = $this->getAuthenticatedUser->handle($currentUser);

        return new SuccessResponse(
            data: new UserResource($profile),
            title: 'OK',
            detail: __('general.retrieved', ['resource' => 'User profile']),
        );
    }
}
