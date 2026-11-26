<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Modules\Media\Events\MediaDeleted;
use Modules\Media\Models\Media;

/**
 * Remove the Media row; the observer captures storage paths in `deleting`
 * and deletes those files only after the row removal commits.
 */
final readonly class DeleteMediaAction
{
    public function handle(Media $media): bool
    {
        $deleted = (bool) $media->delete();

        if ($deleted) {
            event(new MediaDeleted($media));
        }

        return $deleted;
    }
}
