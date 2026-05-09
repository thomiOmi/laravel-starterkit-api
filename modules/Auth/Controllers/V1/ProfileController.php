<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\UpdatePasswordAction;
use Modules\Auth\Actions\UpdateProfileAction;
use Modules\User\Models\User;

/**
 * @tags Authentication
 */
class ProfileController extends Controller
{
    use ApiResponser;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected UpdateProfileAction $updateProfileAction,
        protected UpdatePasswordAction $updatePasswordAction
    ) {}

    /**
     * Update user profile.
     *
     * @param  Request  $request  The current request.
     * @return JsonResponse The JSON response containing user profile.
     */
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<string, mixed> $input */
        $input = $request->all();

        $this->updateProfileAction->execute($user, $input);

        return $this->successResponse(['user' => $user], __('auth.profile_updated'));
    }

    /**
     * Update user password.
     *
     * @param  Request  $request  The current request.
     * @return JsonResponse The success response.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<string, mixed> $input */
        $input = $request->all();

        $this->updatePasswordAction->execute($user, $input);

        return $this->successResponse(null, __('auth.password_updated'));
    }
}
