<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

final readonly class SocialRedirectAction
{
    /** @var array<int, string> */
    private const array ALLOWED_PROVIDERS = ['google', 'github'];

    public function handle(string $provider): string
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            throw new InvalidArgumentException(__('validation.social_provider_invalid'));
        }

        $state = SocialState::create('login');

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $driver->stateless()->with(['state' => $state])->redirect()->getTargetUrl();
    }
}
