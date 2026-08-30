<?php

declare(strict_types=1);

namespace Modules\Media\Policies;

use App\Contracts\Identity;
use App\Enums\PermissionEnum;
use Illuminate\Database\Eloquent\Model;
use Modules\Media\Models\Media;

/**
 * Media Policy
 *
 * Authorization rules for the Media model.
 *
 * Owners (model or uploader) always manage their own uploads; staff with the media.*
 * permissions may inspect and clean up anyone's media. The SuperAdmin
 * bypass is handled globally by the gate "before" hook registered in
 * AppServiceProvider.
 */
final class MediaPolicy
{
    /**
     * Determine whether the user can view the media item.
     */
    public function view(Identity $user, Media $media): bool
    {
        if ($media->isPublic()) {
            return true;
        }

        if ($user instanceof Model && $media->belongsToModel($user)) {
            return true;
        }

        if ($user instanceof Model && $user->is($media->uploadedBy)) {
            return true;
        }

        return $user->can(PermissionEnum::MediaView->value);
    }

    /**
     * Determine whether the user can delete the media item.
     */
    public function delete(Identity $user, Media $media): bool
    {
        if ($user instanceof Model && $media->belongsToModel($user)) {
            return true;
        }

        if ($user instanceof Model && $user->is($media->uploadedBy)) {
            return true;
        }

        return $user->can(PermissionEnum::MediaDelete->value);
    }
}
