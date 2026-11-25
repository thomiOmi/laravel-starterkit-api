<?php

declare(strict_types=1);

namespace Modules\Media\Observers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Media\Models\Media;
use Modules\Media\Support\FileRemover\MediaFileRemover;
use Modules\Media\Support\FileRemover\MediaFilesSnapshot;
use Throwable;

final class MediaObserver
{
    public function deleting(Media $media): void
    {
        // Conversion rows cascade with the row, so capture paths now and
        // delete files only once the row removal has been committed.
        $snapshot = MediaFilesSnapshot::capture($media);

        DB::afterCommit(static function () use ($snapshot): void {
            try {
                resolve(MediaFileRemover::class)->removeCapturedFiles($snapshot);
            } catch (Throwable $throwable) {
                Log::warning('Media file cleanup failed.', [
                    'media_id' => $snapshot->mediaId,
                    'path' => $snapshot->files[0]['path'] ?? '',
                    'disk' => $snapshot->files[0]['disk'] ?? '',
                    'error' => $throwable->getMessage(),
                ]);
            }
        });
    }
}
