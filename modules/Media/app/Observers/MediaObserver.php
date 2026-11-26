<?php

declare(strict_types=1);

namespace Modules\Media\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Modules\Media\Models\Media;
use Modules\Media\Support\FileRemover\MediaFileRemover;
use Modules\Media\Support\FileRemover\MediaFilesSnapshot;
use Throwable;
use WeakMap;

final class MediaObserver implements ShouldHandleEventsAfterCommit
{
    /** @var WeakMap<Media, MediaFilesSnapshot> */
    private static WeakMap $snapshots;

    public function __construct()
    {
        self::$snapshots ??= new WeakMap;
    }

    public function deleting(Media $media): void
    {
        // Conversion rows cascade with the row, so capture paths before the
        // DELETE runs. `deleting` itself is never deferred by the dispatcher.
        self::$snapshots[$media] = MediaFilesSnapshot::capture($media);
    }

    public function deleted(Media $media): void
    {
        $snapshot = self::$snapshots[$media] ?? null;

        if ($snapshot === null) {
            return;
        }

        unset(self::$snapshots[$media]);

        try {
            resolve(MediaFileRemover::class)->removeCapturedFiles($snapshot);
        } catch (Throwable $throwable) {
            Log::warning('Media file deletion failed.', [
                'media_id' => $snapshot->mediaId,
                'path' => $snapshot->files[0]['path'] ?? '',
                'disk' => $snapshot->files[0]['disk'] ?? '',
                'files' => $snapshot->files,
                'error' => $throwable->getMessage(),
            ]);
        }
    }
}
