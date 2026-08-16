<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Enums\RoleEnum;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\IAM\Models\SocialAccount;
use Modules\IAM\Models\User;
use Modules\IAM\Services\UserAuthorizationService;
use Modules\IAM\Support\SocialState;

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
    public function handle(string $provider, string $state, string $ipAddress, ?string $userAgent): array
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            throw new InvalidArgumentException(__('validation.social_provider_invalid'));
        }

        $payload = SocialState::verify($state);

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        try {
            /** @var SocialiteUser $socialUser */
            $socialUser = $driver->stateless()->user();
        } catch (InvalidStateException) {
            throw new InvalidArgumentException(__('auth.social_denied'));
        }

        $user = DB::transaction(function () use ($provider, $socialUser, $payload): User {
            if ($payload['action'] === 'link') {
                return $this->linkAccount($provider, $socialUser, $payload);
            }

            return $this->loginOrCreate($provider, $socialUser);
        });

        $token = $this->authorization->createAccessToken(
            $user,
            $provider.'-social-login',
            $ipAddress,
            $userAgent,
        );

        return [
            'user' => $user,
            ...$token,
        ];
    }

    /**
     * @param  array{action: string, user_id?: string, exp: int}  $payload
     */
    private function linkAccount(string $provider, SocialiteUser $socialUser, array $payload): User
    {
        if (! is_string($payload['user_id'] ?? null)) {
            throw new InvalidArgumentException(__('validation.social_state_invalid'));
        }

        $userId = $payload['user_id'];

        $user = User::query()->find($userId);

        if ($user === null) {
            throw new InvalidArgumentException(__('validation.social_state_invalid'));
        }

        $existing = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', (string) $socialUser->getId())
            ->first();

        if ($existing !== null) {
            throw new InvalidArgumentException(__('validation.social_account_exists'));
        }

        SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => (string) $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
        ]);

        return $user;
    }

    private function loginOrCreate(string $provider, SocialiteUser $socialUser): User
    {
        $providerId = (string) $socialUser->getId();

        $existing = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($existing !== null) {
            return $existing->user()->firstOrFail();
        }

        $email = $socialUser->getEmail();

        if (is_string($email) && $email !== '') {
            $user = User::query()
                ->where('email', $email)
                ->first();

            if ($user !== null) {
                // The provider proves ownership of the email, so an existing
                // unverified account is bound and verified on the spot.
                if ($user->email_verified_at === null) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }

                SocialAccount::query()->create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'avatar' => $socialUser->getAvatar(),
                ]);

                return $user;
            }
        }

        $user = new User([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Social User',
            'email' => is_string($email) && $email !== '' ? $email : "{$provider}-{$providerId}@social.local",
            'password' => null,
            'avatar' => $socialUser->getAvatar(),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $user->assignRole(RoleEnum::User);

        SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $providerId,
            'avatar' => $socialUser->getAvatar(),
        ]);

        return $user;
    }
}
