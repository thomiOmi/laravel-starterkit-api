<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Handles image processing for media uploads.
 */
interface MediaProcessor
{
    /**
     * Determine whether the file can be processed as an image.
     */
    public function canProcess(string $mimeType): bool;

    /**
     * Process the uploaded file and return the processed mime type and path.
     *
     * @return array{mime_type: string, path: string, width: int|null, height: int|null}
     */
    public function process(UploadedFile $file, string $collection, string $disk): array;
}
