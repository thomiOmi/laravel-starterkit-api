<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Modules\Media\Support\ImageDimensions;

covers(ImageDimensions::class);

describe('ImageDimensions support', function () {
    it('measures real image files without a full decode', function () {
        // Keep the fake instance alive: the temp file is removed with it.
        $file = UploadedFile::fake()->image('photo.jpg', 64, 48);

        expect(ImageDimensions::measure($file->getPathname()))->toBe(['width' => 64, 'height' => 48]);
    });

    it('returns null for non-images', function () {
        $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello world');

        expect(ImageDimensions::measure($file->getPathname()))->toBeNull();
    });

    it('flags dimensions beyond the configured limits', function () {
        $file = UploadedFile::fake()->image('photo.jpg', 64, 48);
        $path = $file->getPathname();

        expect(ImageDimensions::exceedsLimits($path, 10, 8000, 25000000))->toBeTrue()
            ->and(ImageDimensions::exceedsLimits($path, 8000, 10, 25000000))->toBeTrue()
            ->and(ImageDimensions::exceedsLimits($path, 8000, 8000, 100))->toBeTrue()
            ->and(ImageDimensions::exceedsLimits($path, 8000, 8000, 25000000))->toBeFalse();
    });
});
