<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\Resources\UserResource;

/**
 * @tags Auth
 */
class SocialAuthController extends Controller
{
    public function redirect(string $provider): JsonResponse
    {
        return new JsonDataResponse(data: [
            'url' => Socialite::driver($provider)->stateless()->redirect()->getTargetUrl(),
        ]);
    }

    public function callback(string $provider, LoginAction $action): JsonResponse
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $dto = new LoginDTO(
            email: (string) $socialUser->getEmail(),
            password: '',
            provider: $provider,
            provider_id: (string) $socialUser->getId()
        );

        $result = $action->execute($dto);

        return new JsonDataResponse(
            data: [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            message: __('auth.login_success')
        );
    }
}
