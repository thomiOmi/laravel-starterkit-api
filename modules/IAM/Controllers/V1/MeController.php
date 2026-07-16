<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\IAM\Actions\GetAuthenticatedUserAction;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\UserResource;

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
     * @return SuccessResponse<UserResource>
     */
    public function __invoke(Request $request): SuccessResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        $profile = $this->getAuthenticatedUser->handle($currentUser);

        return new SuccessResponse(
            data: new UserResource($profile),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'User profile']),
        );
    }
}
