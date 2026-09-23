<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Modules\Media\Support\MediaMimeType;

covers(MediaMimeType::class);

describe('MediaMimeType support', function (): void {
    it('sniffs image bytes regardless of the client mime', function (): void {
        // Keep the fake instance alive: the temp file is removed with it.
        $file = UploadedFile::fake()->image('photo.jpg', 20, 20);

        expect(MediaMimeType::detect($file->getPathname()))->toBe('image/jpeg');
    });

    it('sniffs pdf and text buffers', function (): void {
        expect(MediaMimeType::detectFromContent("%PDF-1.4\nbody\n%%EOF"))->toBe('application/pdf')
            ->and(MediaMimeType::detectFromContent('hello world'))->toBe('text/plain');
    });

    it('flags executable and markup content as blocked', function (): void {
        expect(MediaMimeType::isBlocked('application/x-php'))->toBeTrue()
            ->and(MediaMimeType::isBlocked('text/html'))->toBeTrue()
            ->and(MediaMimeType::isBlocked('image/svg+xml'))->toBeTrue()
            ->and(MediaMimeType::isBlocked('image/jpeg'))->toBeFalse()
            ->and(MediaMimeType::isBlocked('application/pdf'))->toBeFalse();
    });

    it('matches extensions against sniffed mimes and fails open on unknowns', function (): void {
        expect(MediaMimeType::extensionMatchesMime('png', 'image/png'))->toBeTrue()
            ->and(MediaMimeType::extensionMatchesMime('jpg', 'image/jpeg'))->toBeTrue()
            ->and(MediaMimeType::extensionMatchesMime('png', 'text/plain'))->toBeFalse()
            ->and(MediaMimeType::extensionMatchesMime('pdf', 'image/png'))->toBeFalse()
            ->and(MediaMimeType::extensionMatchesMime('unknownext123', 'application/octet-stream'))->toBeTrue();
    });
});
