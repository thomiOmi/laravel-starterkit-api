<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Services\TwoFactorService;

class TwoFactorController extends Controller
{
    use ApiResponser;

    public function __construct(protected TwoFactorService $twoFactorService) {}

    public function enable(Request $request): JsonResponse
    {
        $result = $this->twoFactorService->enable($request->user());

        return $this->successResponse($result, __('auth.two_factor_enabled'));
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $confirmed = $this->twoFactorService->confirm($request->user(), $request->input('code'));
        if (! $confirmed) {
            return $this->errorResponse(__('auth.two_factor_invalid_code'), 422);
        }

        return $this->successResponse(null, __('auth.two_factor_confirmed'));
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($request->input('password'), $request->user()->password)) {
            return $this->errorResponse(__('auth.password_invalid'), 422);
        }
        $this->twoFactorService->disable($request->user());

        return $this->successResponse(null, __('auth.two_factor_disabled'));
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->two_factor_recovery_codes) {
            return $this->successResponse([], 'No recovery codes found.');
        }

        return $this->successResponse(json_decode(decrypt($user->two_factor_recovery_codes), true), 'Recovery codes retrieved successfully.');
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $codes = $this->twoFactorService->generateRecoveryCodes($request->user());

        return $this->successResponse($codes, 'Recovery codes regenerated successfully.');
    }
}
