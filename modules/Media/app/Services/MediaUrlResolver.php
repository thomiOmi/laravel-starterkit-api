<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use App\Contracts\Identity;
use App\Enums\MediaVisibilityEnum;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Uri;
use InvalidArgumentException;
use Modules\IAM\Models\User;
use Modules\Media\Models\Media;

/**
 * Resolves stored media references into public URLs.
 *
 * Owner-scoped resolution additionally requires the media to live on the
 * configured public disk inside the requested collection. Public
 * resolution ignores ownership but still honours visibility and disk.
 */
final readonly class MediaUrlResolver implements \App\Contracts\MediaUrlResolver
{
    #[\Override]
    public function forOwner(string $mediaId, Identity $user, string $collection): string
    {
        /** @var User $user */
        $media = Media::query()->find($mediaId);

        if (
            $media === null
            || ! $media->isOwnedBy($user->id)
            || $media->collection_name !== $collection
            || $media->disk !== config()->string('media.disk', 'public')
        ) {
            throw new InvalidArgumentException(__('validation.media_unavailable'));
        }

        return Storage::disk($media->disk)->url($media->path);
    }

    #[\Override]
    public function public(string $mediaId): ?string
    {
        $media = Media::query()->find($mediaId);

        if (
            $media === null
            || $media->visibility !== MediaVisibilityEnum::Public
            || $media->disk !== config()->string('media.disk', 'public')
        ) {
            return null;
        }

        return Storage::disk($media->disk)->url($media->path);
    }

    #[\Override]
    public function signed(string $mediaId, int $ttlMinutes): string
    {
        $media = Media::query()->find($mediaId);

        if (
            $media === null
            || $media->disk !== config()->string('media.disk', 'public')
        ) {
            throw new InvalidArgumentException(__('validation.media_unavailable'));
        }

        return (string) Uri::temporarySignedRoute(
            'api.v1.media.file',
            now()->addMinutes($ttlMinutes),
            ['media' => $media->id],
        );
    }
}
