<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Encryption\EncryptException;
use InvalidArgumentException;
use JsonException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Modules\IAM\Support\SocialState;

final readonly class SocialRedirectAction
{
    /** @var array<int, string> */
    private const array ALLOWED_PROVIDERS = ['google', 'github'];

    /**
     * Build the provider redirect URL for a stateless login.
     *
     * @throws InvalidArgumentException When the provider is unsupported.
     * @throws EncryptException When the state token cannot be encrypted.
     * @throws JsonException When the state payload cannot be JSON-encoded.
     */
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
