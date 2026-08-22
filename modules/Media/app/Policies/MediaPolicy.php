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
 * Owners always manage their own uploads; staff with the media.*
 * permissions may inspect and clean up anyone's media. The SuperAdmin
 * bypass is handled globally by the gate "before" hook registered in
 * AppServiceProvider.
 */
final class MediaPolicy
{
    /**
     * Determine whether the user can view the media item.
     */
    public function view(User $user, Media $media): bool
    {
        return $user->is($media->uploadedBy) || $user->can(PermissionEnum::MediaView->value);
    }

    /**
     * Determine whether the user can delete the media item.
     */
    public function delete(User $user, Media $media): bool
    {
        return $user->is($media->uploadedBy) || $user->can(PermissionEnum::MediaDelete->value);
    }
}
