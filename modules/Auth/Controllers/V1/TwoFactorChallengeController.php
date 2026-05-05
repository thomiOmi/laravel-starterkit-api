<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Services\TwoFactorService;
use Modules\User\Repositories\UserRepository;

class TwoFactorChallengeController extends Controller
{
    use ApiResponser;

    public function __construct(
        protected TwoFactorService $twoFactorService,
        protected UserRepository $userRepository
    ) {}

    public function challenge(Request $request): JsonResponse
    {
        $request->validate([
            'two_factor_token' => ['required', 'string'],
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code'],
        ]);

        $token = $request->input('two_factor_token');
        $data = Cache::get("2fa_challenge:{$token}");
        if (! $data) {
            return $this->errorResponse('Two factor challenge expired or invalid.', 422);
        }

        $user = $this->userRepository->findById($data['user_id']);
        $isValid = false;

        if ($request->filled('code')) {
            $secret = decrypt($user->two_factor_secret);
            $isValid = $this->twoFactorService->verify($secret, $request->input('code'));
        } elseif ($request->filled('recovery_code')) {
            $isValid = $this->twoFactorService->verifyRecoveryCode($user, $request->input('recovery_code'));
        }

        if (! $isValid) {
            return $this->errorResponse(__('auth.two_factor_invalid_code'), 422);
        }

        Cache::forget("2fa_challenge:{$token}");
        $tokenResult = $user->createToken($data['device_name'], ['*']);
        $tokenResult->accessToken->forceFill([
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
        ])->save();

        return $this->successResponse([
            'user' => $user,
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
        ], __('auth.login_success'));
    }
}
