<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Modules\Media\Events\MediaDeleted;
use Modules\Media\Models\Media;

/**
 * Remove the Media row; storage cleanup runs once in the observer
 * deleting hook while conversion rows are still readable.
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
