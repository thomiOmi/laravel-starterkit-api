<?php

declare(strict_types=1);

namespace Modules\Media\Observers;

use Modules\Media\Models\Media;
use Modules\Media\Support\FileRemover\MediaFileRemover;

final class MediaObserver
{
    public function deleting(Media $media): void
    {
        // Fallback cleanup when rows are removed outside DeleteMediaAction.
        // Storage deletes are idempotent, so double runs stay harmless.
        app(MediaFileRemover::class)->removeAllFiles($media);
    }

    public function deleted(Media $media): void
    {
        // No-op: DeleteMediaAction already removed files; the deleting
        // hook above covers rows removed by any other path.
    }
}
