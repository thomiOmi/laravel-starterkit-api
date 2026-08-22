<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;

/**
 * Remove the stored file and its Media row.
 */
final readonly class DeleteMediaAction
{
    public function handle(Media $media): bool
    {
        Storage::disk($media->disk)->delete($media->path);

        return (bool) $media->delete();
    }
}
