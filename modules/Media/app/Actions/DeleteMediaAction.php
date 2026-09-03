<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Media\Events\MediaDeleted;
use Modules\Media\Models\Media;

/**
 * Remove the stored file, its derived variants, and its Media row.
 *
 * The row is removed first so a failed storage delete leaves an orphan
 * file (harmless, sweepable) instead of a dangling database reference.
 */
final readonly class DeleteMediaAction
{
    public function handle(Media $media): bool
    {
        $disk = $media->disk;
        $path = $media->path;
        $mediaId = $media->id;
        $conversions = $media->conversions()->get();

        $deleted = (bool) $media->delete();

        if ($deleted) {
            Storage::disk($disk)->delete($path);
            Storage::disk($disk)->deleteDirectory('variants/'.$mediaId);
            Storage::disk($disk)->deleteDirectory('conversions/'.$mediaId);

            foreach ($conversions as $conversion) {
                Storage::disk($conversion->disk)->delete($conversion->path);
            }

            event(new MediaDeleted($media));
        }

        return $deleted;
    }
}
