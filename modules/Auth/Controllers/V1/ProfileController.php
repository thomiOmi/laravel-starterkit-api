<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\UpdatePasswordAction;
use Modules\Auth\Actions\UpdateProfileAction;

class ProfileController extends Controller
{
    use ApiResponser;

    public function __construct(
        protected UpdateProfileAction $updateProfileAction,
        protected UpdatePasswordAction $updatePasswordAction
    ) {}

    public function update(Request $request): JsonResponse
    {
        $this->updateProfileAction->execute($request->user(), $request->all());

        return $this->successResponse(['user' => $request->user()], __('auth.profile_updated'));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $this->updatePasswordAction->execute($request->user(), $request->all());

        return $this->successResponse(null, __('auth.password_updated'));
    }
}
