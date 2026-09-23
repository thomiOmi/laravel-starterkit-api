<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Image\Image;
use Illuminate\Support\Facades\Image as ImageFacade;
use Modules\Media\Support\ImageManipulations;

covers(ImageManipulations::class);

describe('ImageManipulations', function (): void {
    function manipImage(): Image
    {
        return ImageFacade::fromUpload(UploadedFile::fake()->image('manip.jpg', 10, 10));
    }

    it('applies grayscale via filter', function (): void {
        $result = ImageManipulations::apply(manipImage(), ['filter' => 'grayscale']);

        expect((string) $result)->not->toBeEmpty();
    });

    it('rejects unknown filters', function (): void {
        expect(fn (): Image => ImageManipulations::apply(manipImage(), ['filter' => 'sepia']))
            ->toThrow(InvalidArgumentException::class, 'Unsupported image filter [sepia].');
    });

    it('rejects non-string filters', function (): void {
        expect(fn (): Image => ImageManipulations::apply(manipImage(), ['filter' => 123]))
            ->toThrow(InvalidArgumentException::class, 'Image filter must be a string.');
    });

    it('applies blur and sharpen within bounds', function (): void {
        $result = ImageManipulations::apply(manipImage(), ['blur' => 5, 'sharpen' => 3]);

        expect((string) $result)->not->toBeEmpty();
    });

    it('rejects non-integer blur level', function (): void {
        expect(fn (): Image => ImageManipulations::apply(manipImage(), ['blur' => 'high']))
            ->toThrow(InvalidArgumentException::class, 'Image blur level must be an integer.');
    });

    it('rejects out of bounds blur level', function (): void {
        expect(fn (): Image => ImageManipulations::apply(manipImage(), ['blur' => 101]))
            ->toThrow(InvalidArgumentException::class, 'Image blur level must be between 0 and 100.');
    });

    it('rejects out of bounds sharpen level', function (): void {
        expect(fn (): Image => ImageManipulations::apply(manipImage(), ['sharpen' => -1]))
            ->toThrow(InvalidArgumentException::class, 'Image sharpen level must be between 0 and 100.');
    });

    it('applies rotate with numeric angle', function (): void {
        $result = ImageManipulations::apply(manipImage(), ['rotate' => 90]);

        expect((string) $result)->not->toBeEmpty();
    });

    it('rejects non-numeric rotate angle', function (): void {
        expect(fn (): Image => ImageManipulations::apply(manipImage(), ['rotate' => 'left']))
            ->toThrow(InvalidArgumentException::class, 'Image rotation angle must be numeric.');
    });

    it('returns the image unchanged when no manipulations are present', function (): void {
        $result = ImageManipulations::apply(manipImage(), []);

        expect((string) $result)->not->toBeEmpty();
    });
});
