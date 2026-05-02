<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Http\UploadedFile;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaLibrary;

class UploadMediaAction
{
    /**
     * Upload media to the default media library.
     */
    public function execute(UploadedFile $file, ?string $collection = 'default'): Media
    {
        $library = MediaLibrary::firstOrCreate([
            'tenant_id' => tenant('id'),
            'name' => 'default',
        ]);

        /** @var Media $media */
        $media = $library->addMedia($file)
            ->toMediaCollection($collection);

        return $media;
    }
}
