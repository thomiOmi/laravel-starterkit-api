<?php

declare(strict_types=1);

namespace Modules\Media\Policies;

use App\Enums\PermissionEnum;
use Modules\IAM\Models\User;
use Modules\Media\Models\Media;

/**
 * Media Policy
 *
 * Authorization rules for the Media model.
 *
 * The SuperAdmin bypass is handled globally by the gate "before" hook
 * registered in AppServiceProvider, so policies only need to guard
 * against non-super-admin actors.
 */
final class MediaPolicy
{
    /**
     * Determine whether the user can view the media.
     */
    public function view(User $user, Media $media): bool
    {
        return $user->can(PermissionEnum::MediaView->value);
    }

    /**
     * Determine whether the user can upload media.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::MediaCreate->value);
    }

    /**
     * Determine whether the user can delete the media.
     *
     * The uploader can always delete their own media; anyone holding
     * the `media.delete` permission can delete any media.
     */
    public function delete(User $user, Media $media): bool
    {
        return $user->can(PermissionEnum::MediaDelete->value)
            || $media->uploaded_by === $user->getKey();
    }
}
