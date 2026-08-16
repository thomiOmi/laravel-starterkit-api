<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Resolves a media reference to a public avatar URL for an authenticated user.
 *
 * Cross-module contract implemented by the Media module (bound in its
 * provider). The IAM module consumes it through the container; when no
 * implementation is bound (Media module absent or inactive), the consumer
 * receives null and the avatar feature degrades gracefully.
 *
 * @throws \InvalidArgumentException when the media does not exist, is not
 *                                   stored on the public disk, or belongs
 *                                   to another user.
 */
interface AvatarResolver
{
    /**
     * Resolve the public URL of a media item owned by the given user.
     */
    public function resolve(string $mediaId, Identity $user): string;
}
