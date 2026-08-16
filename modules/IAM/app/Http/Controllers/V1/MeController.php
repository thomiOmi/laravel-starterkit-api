<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Http\Resources\UserResource;
use Modules\IAM\Models\User;

final readonly class MeController extends Controller
{
    /**
     * Me.
     *
     * Retrieves the profile information of the currently authenticated user.
     *
     * @return SuccessResponse<UserResource>
     */
    public function __invoke(#[CurrentUser] User $currentUser): SuccessResponse
    {
        $currentUser->load('roles', 'permissions');

        return new SuccessResponse(
            data: new UserResource($currentUser),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'User profile']),
        );
    }
}
