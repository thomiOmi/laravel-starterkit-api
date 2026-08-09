<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use JsonException;

/**
 * Self-contained signed state token for the stateless OAuth flow.
 *
 * The token carries the intended action (login or link) and, for linking,
 * the target user id. It is encrypted with the application key so it cannot
 * be forged or tampered with, and expires after a short window.
 */
final class SocialState
{
    /** Minutes a state token stays valid. */
    private const int TTL_MINUTES = 10;

    /**
     * Create a signed state token.
     *
     * @param  array<string, string>  $payload  The payload to embed.
     */
    public static function create(string $action, array $payload = []): string
    {
        return Crypt::encryptString(json_encode([
            'action' => $action,
            'exp' => now()->addMinutes(self::TTL_MINUTES)->getTimestamp(),
            ...$payload,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * Verify a state token and return its payload.
     *
     * @return array{action: string, user_id?: string, exp: int}
     *
     * @throws InvalidArgumentException When the token is invalid or expired.
     */
    public static function verify(string $state): array
    {
        try {
            $payload = json_decode((string) Crypt::decryptString($state), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            throw new InvalidArgumentException(__('validation.social_state_invalid'));
        }

        if (! is_array($payload) || ! isset($payload['action'], $payload['exp'])
            || ! is_string($payload['action']) || ! is_int($payload['exp'])) {
            throw new InvalidArgumentException(__('validation.social_state_invalid'));
        }

        if ($payload['exp'] < now()->getTimestamp()) {
            throw new InvalidArgumentException(__('validation.social_state_expired'));
        }

        $verified = [
            'action' => $payload['action'],
            'exp' => $payload['exp'],
        ];

        if (is_string($payload['user_id'] ?? null)) {
            $verified['user_id'] = $payload['user_id'];
        }

        return $verified;
    }
}
