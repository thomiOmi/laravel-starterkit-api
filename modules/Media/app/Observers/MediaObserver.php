<?php

declare(strict_types=1);

namespace Modules\Media\Observers;

use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;

final class MediaObserver
{
    public function deleting(Media $media): void
    {
        // Delete conversion files
        foreach ($media->conversions()->get() as $conversion) {
            Storage::disk($conversion->disk)->delete($conversion->path);
        }

        // Delete variants and conversions directories
        Storage::disk($media->disk)->deleteDirectory('variants/'.$media->id);
        Storage::disk($media->disk)->deleteDirectory('conversions/'.$media->id);
    }

    public function deleted(Media $media): void
    {
        // Also ensure the original file is deleted if not already (DeleteMediaAction already does, but observer is fallback)
        // No-op if already deleted
    }
}
