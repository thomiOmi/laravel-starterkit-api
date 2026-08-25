<?php

declare(strict_types=1);

namespace App\Contracts;

use InvalidArgumentException;

/**
 * Resolves stored media references into usable public URLs.
 *
 * Cross-module contract implemented by the Media module (bound in its
 * provider). Consumers depend on this port only; when no implementation
 * is bound (Media module absent or inactive), the feature degrades
 * gracefully.
 *
 * Scope constraints (ownership, collection) are passed explicitly by the
 * consumer so the port stays generic across media use-cases.
 */
interface MediaUrlResolver
{
    /**
     * Resolve the URL of a media item owned by the given identity.
     *
     * @param  string  $collection  The logical collection the media must belong to.
     *
     * @throws InvalidArgumentException when the media does not exist, is not
     *                                  stored on the configured public disk,
     *                                  lives outside $collection, or belongs
     *                                  to another user.
     */
    public function forOwner(string $mediaId, Identity $user, string $collection): string;

    /**
     * Resolve the URL of a publicly visible media item regardless of owner.
     *
     * Returns null when the media does not exist, is not publicly visible,
     * or is stored outside the configured public disk.
     */
    public function public(string $mediaId): ?string;

    /**
     * Resolve a temporary signed streaming URL for a media item.
     *
     * The signature is the credential: whoever holds the URL may stream the
     * file until it expires, regardless of the media visibility. Consumers
     * MUST only hand these URLs to identities already authorized to see the
     * media.
     *
     * @param  int  $ttlMinutes  Lifetime of the URL in minutes (1..1440).
     *
     * @throws InvalidArgumentException when the media does not exist or is
     *                                  stored outside the configured public
     *                                  disk.
     */
    public function signed(string $mediaId, int $ttlMinutes): string;
}
