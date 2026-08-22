<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use App\Contracts\AvatarResolver;
use App\Contracts\Identity;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\IAM\Models\User;
use Modules\Media\Enums\MediaCollection;
use Modules\Media\Models\Media;

/**
 * Resolves avatar media references to public URLs.
 *
 * The media must exist, belong to the requesting user, and live on the
 * configured public disk inside the avatars collection.
 */
final readonly class MediaAvatarResolver implements AvatarResolver
{
    #[\Override]
    public function resolve(string $mediaId, Identity $user): string
    {
        /** @var User $user */
        $media = Media::query()->find($mediaId);

        if ($media === null || ! $media->isOwnedBy($user->id)) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        if ($media->disk !== config()->string('media.disk', 'public')) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        if ($media->collection_name !== MediaCollection::Avatars->value) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        return Storage::disk($media->disk)->url($media->path);
    }
}
