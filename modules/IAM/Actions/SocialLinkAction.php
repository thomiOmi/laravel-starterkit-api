<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Modules\IAM\Models\User;
use Modules\IAM\Support\SocialState;

final readonly class SocialLinkAction
{
    /** @var array<int, string> */
    private const array ALLOWED_PROVIDERS = ['google', 'github'];

    public function handle(string $provider, User $user): string
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            throw new InvalidArgumentException(__('validation.social_provider_invalid'));
        }

        if ($user->socialAccounts()->where('provider', $provider)->exists()) {
            throw new InvalidArgumentException(__('validation.social_account_exists'));
        }

        $state = SocialState::create('link', ['user_id' => $user->id]);

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $driver->stateless()->with(['state' => $state])->redirect()->getTargetUrl();
    }
}
