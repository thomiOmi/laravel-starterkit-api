<?php

declare(strict_types=1);

namespace Modules\Media\Observers;

use Modules\Media\Models\Media;
use Modules\Media\Support\FileRemover\MediaFileRemover;

final class MediaObserver
{
    public function deleting(Media $media): void
    {
        // Single storage cleanup site: runs while conversion rows are
        // still readable. Storage deletes are idempotent.
        app(MediaFileRemover::class)->removeAllFiles($media);
    }
}
