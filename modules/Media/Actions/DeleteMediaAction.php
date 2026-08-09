<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;

/**
 * Action for deleting a media.
 */
final readonly class DeleteMediaAction
{
    /**
     * Execute the delete media action.
     *
     * Removes the physical file first, then the record. A missing file
     * does not prevent the record from being deleted.
     *
     * @param  Media  $media  The media to delete.
     * @return bool True if the media was deleted successfully, false otherwise.
     */
    public function handle(Media $media): bool
    {
        Storage::disk($media->disk)->delete($media->path);

        return (bool) $media->delete();
    }
}
