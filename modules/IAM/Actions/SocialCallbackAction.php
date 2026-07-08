<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Modules\IAM\Models\User;
use Modules\IAM\Services\UserAuthorizationService;

final readonly class SocialCallbackAction
{
    /** @var array<int, string> */
    private const array ALLOWED_PROVIDERS = ['google', 'github'];

    public function __construct(
        private UserAuthorizationService $authorization,
    ) {}

    /**
     * @return array{user: User, access_token: string, token_type: string, expires_at: ?string, expires_in: ?int}
     */
    public function handle(string $provider, string $ipAddress, ?string $userAgent): array
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            throw new InvalidArgumentException(__('validation.social_provider_invalid'));
        }

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        try {
            /** @var \Laravel\Socialite\Two\User $socialUser */
            $socialUser = $driver->stateless()->user();
        } catch (InvalidStateException) {
            throw new InvalidArgumentException(__('auth.social_denied'));
        }

        $user = DB::transaction(function () use ($provider, $socialUser): User {
            $user = User::with(['roles.permissions:id,name', 'permissions:id,name'])
                ->where('provider', $provider)
                ->where('provider_id', (string) $socialUser->getId())
                ->first();

            if ($user !== null) {
                return $user;
            }

            if ($socialUser->getEmail() !== null && $socialUser->getEmail() !== '') {
                $user = User::with(['roles.permissions:id,name', 'permissions:id,name'])
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

        $token = $this->authorization->createAccessToken(
            $user,
            $provider.'-social-login',
            $ipAddress,
            $userAgent,
        );

        return [
            'user' => $user->loadMissing(['roles.permissions:id,name', 'permissions:id,name']),
            ...$token,
        ];
    }
}
