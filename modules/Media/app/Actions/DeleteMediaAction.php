<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;

/**
 * Remove the stored file and its Media row.
 *
 * The row is removed first so a failed storage delete leaves an orphan
 * file (harmless, sweepable) instead of a dangling database reference.
 */
final readonly class DeleteMediaAction
{
    public function handle(Media $media): bool
    {
        $deleted = (bool) $media->delete();

        if ($deleted) {
            Storage::disk($media->disk)->delete($media->path);
        }

        return $deleted;
    }
}
