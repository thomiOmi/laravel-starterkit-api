<?php

declare(strict_types=1);

use Modules\Media\Models\Media;
use Modules\Media\Support\PathGenerator\DefaultPathGenerator;

covers(DefaultPathGenerator::class);

describe('DefaultPathGenerator derivative directories', function (): void {
    /**
     * Build an in-memory media row. Nothing is persisted: only the id,
     * collection, and file name are read by the generator.
     */
    function generatorProbeMedia(string $id = '01AAAAAAAAAAAAAAAAAAAAAAAA'): Media
    {
        $media = new Media;
        $media->forceFill([
            'id' => $id,
            'collection_name' => 'avatars',
            'file_name' => 'photo.webp',
        ]);

        return $media;
    }

    it('returns the original file path from collection and file name', function (): void {
        expect((new DefaultPathGenerator)->getPath(generatorProbeMedia()))->toBe('avatars/photo.webp');
    });

    it('returns the id-keyed eager conversions directory', function (): void {
        expect((new DefaultPathGenerator)->getPathForConversions(generatorProbeMedia()))
            ->toBe('conversions/01AAAAAAAAAAAAAAAAAAAAAAAA');
    });

    it('returns the id-keyed derived cache directory', function (): void {
        expect((new DefaultPathGenerator)->getPathForDerivedConversions(generatorProbeMedia()))
            ->toBe('conversions/derived/01AAAAAAAAAAAAAAAAAAAAAAAA');
    });

    it('returns the responsive directory next to the original file', function (): void {
        expect((new DefaultPathGenerator)->getPathForResponsiveImages(generatorProbeMedia()))
            ->toBe('avatars/responsive-images');
    });

    it('prepends the configured prefix to the original path and every derivative directory', function (): void {
        config(['media.prefix' => 'media']);

        $generator = new DefaultPathGenerator;
        $media = generatorProbeMedia();

        expect($generator->getPath($media))->toBe('media/avatars/photo.webp')
            ->and($generator->getPathForConversions($media))->toBe('media/conversions/01AAAAAAAAAAAAAAAAAAAAAAAA')
            ->and($generator->getPathForDerivedConversions($media))->toBe('media/conversions/derived/01AAAAAAAAAAAAAAAAAAAAAAAA')
            ->and($generator->getPathForResponsiveImages($media))->toBe('media/avatars/responsive-images');
    });
});
