<?php

declare(strict_types=1);

namespace Modules\Media\Support\FileRemover;

use Modules\Media\Models\Media;

/**
 * Storage targets captured while the media row still exists.
 *
 * Conversion rows cascade with the media row, so their paths cannot be read
 * again after the delete commits. The snapshot keeps every path together with
 * the disk it lives on so cleanup stays on the correct storage.
 */
final readonly class MediaFilesSnapshot
{
    /**
     * @param  list<array{path: string, disk: string}>  $files
     * @param  list<array{directory: string, disk: string}>  $directories
     */
    public function __construct(
        public string $mediaId,
        public array $files,
        public array $directories,
    ) {}

    public static function capture(Media $media): self
    {
        $files = [];
        $originalPath = $media->getPath();

        if (is_string($originalPath) && $originalPath !== '') {
            $files[] = ['path' => $originalPath, 'disk' => $media->disk];
        }

        foreach ($media->conversions()->get() as $conversion) {
            if ($conversion->path !== '') {
                $files[] = ['path' => $conversion->path, 'disk' => $conversion->disk];
            }
        }

        $conversionDisk = $media->conversions_disk ?? $media->disk;
        $responsive = $media->responsive_images;

        if (is_array($responsive)) {
            foreach ($responsive as $info) {
                if ($info['path'] !== '') {
                    $files[] = ['path' => $info['path'], 'disk' => $conversionDisk];
                }
            }
        }

        return new self(
            $media->id,
            $files,
            [
                ['directory' => $media->getDerivedConversionsDirectory(), 'disk' => $conversionDisk],
                ['directory' => $media->getConversionsDirectory(), 'disk' => $conversionDisk],
            ],
        );
    }
}
