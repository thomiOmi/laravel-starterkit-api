<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Modules\Media\Events\MediaDeleted;
use Modules\Media\Models\Media;
use Modules\Media\Support\FileRemover\MediaFileRemover;

/**
 * Remove the stored file, its derived variants, and its Media row.
 *
 * The row is removed first so a failed storage delete leaves an orphan
 * file (harmless, sweepable) instead of a dangling database reference.
 * Physical removal goes through the configured file remover.
 */
final readonly class DeleteMediaAction
{
    public function handle(Media $media): bool
    {
        $deleted = (bool) $media->delete();

        if ($deleted) {
            app(MediaFileRemover::class)->removeAllFiles($media);

            event(new MediaDeleted($media));
        }

        return $deleted;
    }
}
