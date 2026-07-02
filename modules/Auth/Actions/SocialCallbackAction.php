<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialUser;
use Modules\User\Models\User;

final readonly class SocialCallbackAction
{
    /** @var array<int, string> */
    private const array ALLOWED_PROVIDERS = ['google', 'github'];

    /**
     * @return array{user: User, access_token: string, token_type: string}
     */
    public function handle(string $provider, string $ipAddress, ?string $userAgent): array
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            throw new InvalidArgumentException(__('validation.social_provider_invalid'));
        }

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        try {
            /** @var SocialUser $socialUser */
            $socialUser = $driver->stateless()->user();
        } catch (InvalidStateException) {
            throw new InvalidArgumentException(__('auth.social_denied'));
        }

        /** @var User $user */
        $user = DB::transaction(function () use ($provider, $socialUser): User {
            $user = User::with(['roles.permissions:id,name', 'permissions:id,name'])
                ->select([
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ])
                ->where('provider', $provider)
                ->where('provider_id', (string) $socialUser->getId())
                ->first();

            if ($user !== null) {
                return $user;
            }

            if ($socialUser->getEmail() !== null && $socialUser->getEmail() !== '') {
                $user = User::with(['roles.permissions:id,name', 'permissions:id,name'])
                    ->select([
                        'id',
                        'name',
                        'email',
                        'avatar',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ])
                    ->where('email', $socialUser->getEmail())
                    ->first();

                if ($user !== null) {
                    $user->update([
                        'provider' => $provider,
                        'provider_id' => (string) $socialUser->getId(),
                        'avatar' => $socialUser->getAvatar(),
                    ]);

                    return $user;
                }
            }

            /** @var User $user */
            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Social User',
                'email' => $socialUser->getEmail() ?? "{$provider}-{$socialUser->getId()}@social.local",
                'password' => null,
                'provider' => $provider,
                'provider_id' => (string) $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);

            $user->assignRole('user');

            return $user;
        });

        $abilities = $user->hasRole(['admin', 'super-admin'])
            ? ['*']
            : ['users:read', 'users:write', 'auth:manage'];

        $token = $user->createToken(
            $provider.'-social-login',
            $abilities,
        );

        /** @var PersonalAccessToken $accessToken */
        $accessToken = $token->accessToken;

        $accessToken->forceFill([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ])->save();

        /** @var User $user */
        $user = $user->loadMissing(['roles.permissions:id,name', 'permissions:id,name']);

        return [
            'user' => $user,
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}
