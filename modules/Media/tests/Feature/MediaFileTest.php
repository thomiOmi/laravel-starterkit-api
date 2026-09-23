<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Uri;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Http\Controllers\V1\MediaFileController;

covers(MediaFileController::class);

describe('GET /api/v1/media/{media}/file', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
    });

    it('streams the stored file for a valid signed url of private media', function (): void {
        $media = MediaFactory::new()->createOne(['mime_type' => 'image/png']);
        Storage::disk($media->disk)->put($media->getPath() ?? '', (string) UploadedFile::fake()->image('private.png')->getContent());

        $url = $media->signedUrl(15);

        $response = $this->get($url);

        $response->assertOk();

        expect($response->headers->get('Content-Type'))->toContain('image/png');
        Storage::disk($media->disk)->assertExists($media->getPath() ?? '');
    });

    it('streams non-image files with their stored mime type', function (): void {
        $media = MediaFactory::new()->createOne([
            'file_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
        ]);
        Storage::disk($media->disk)->put($media->getPath() ?? '', '%PDF-1.4');

        $response = $this->get($media->signedUrl(15));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('application/pdf')
            ->and($response->headers->get('Content-Disposition'))->toContain('document.pdf')
            ->and(Storage::disk($media->disk)->get($media->getPath() ?? ''))->toBe('%PDF-1.4');
    });

    it('rejects tampered signatures', function (): void {
        $media = MediaFactory::new()->createOne();
        Storage::disk($media->disk)->put($media->getPath() ?? '', 'content');

        $url = $media->signedUrl(15);
        $tampered = str_replace('signature=', 'signature=x', $url);

        $this->get($tampered)->assertForbidden();
    });

    it('rejects expired signatures', function (): void {
        $media = MediaFactory::new()->createOne();
        Storage::disk($media->disk)->put($media->getPath() ?? '', 'content');

        $expired = (string) Uri::temporarySignedRoute(
            'api.v1.media.file',
            now()->subMinute(),
            ['media' => $media->id],
        );

        $this->get($expired)->assertForbidden();
    });

    it('rejects requests without a signature', function (): void {
        $media = MediaFactory::new()->createOne();

        $this->get("/api/v1/media/{$media->id}/file")->assertForbidden();
    });

    it('returns 404 when the underlying file is missing', function (): void {
        $media = MediaFactory::new()->createOne();

        $url = $media->signedUrl(15);

        assertProblemResponse($this->get($url), 404, 'resource-not-found');
    });
});
