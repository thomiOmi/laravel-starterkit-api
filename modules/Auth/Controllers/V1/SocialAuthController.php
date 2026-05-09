<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Modules\User\Repositories\UserRepository;
use Throwable;

/**
 * @tags Authentication
 */
class SocialAuthController extends Controller
{
    use ApiResponser;

    /**
     * Create a new controller instance.
     */
    public function __construct(protected UserRepository $userRepository) {}

    /**
     * Redirect to social provider.
     *
     * @param  string  $provider  The social provider name (e.g. google, github).
     * @return RedirectResponse The redirect response to provider.
     */
    public function redirect(string $provider): RedirectResponse
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $driver->stateless()->redirect();
    }

    /**
     * Handle social provider callback.
     *
     * @param  string  $provider  The social provider name.
     * @return JsonResponse The JSON response with token.
     */
    public function callback(string $provider): JsonResponse
    {
        try {
            /** @var AbstractProvider $driver */
            $driver = Socialite::driver($provider);
            /** @var User $socialUser */
            $socialUser = $driver->stateless()->user();
        } catch (Throwable $e) {
            return $this->errorResponse('Social authentication failed. Please try again.', 422);
        }

        /** @var string $socialEmail */
        $socialEmail = $socialUser->getEmail();

        /** @var \Modules\User\Models\User $user */
        $user = $this->userRepository->updateOrCreate(
            ['email' => $socialEmail],
            [
                'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'email_verified_at' => now(),
                'password' => null,
            ]
        );

        $token = $user->createToken($provider.'-login', ['*'])->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], __('auth.login_success'));
    }
}
