<?php

declare(strict_types=1);

use App\Contracts\MediaUrlResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Uri;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Http\Controllers\V1\MediaFileController;

covers(MediaFileController::class);

describe('GET /api/v1/media/{media}/file', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('streams the stored file for a valid signed url of private media', function () {
        $media = MediaFactory::new()->createOne(['mime_type' => 'image/png']);
        Storage::disk($media->disk)->put($media->path, (string) UploadedFile::fake()->image('private.png')->getContent());

        $url = app(MediaUrlResolver::class)->signed($media->id, 15);

        $response = $this->get($url);

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('image/png');
        Storage::disk($media->disk)->assertExists($media->path);
    });

    it('rejects tampered signatures', function () {
        $media = MediaFactory::new()->createOne();
        Storage::disk($media->disk)->put($media->path, 'content');

        $url = app(MediaUrlResolver::class)->signed($media->id, 15);
        $tampered = str_replace('signature=', 'signature=x', $url);

        $this->get($tampered)->assertForbidden();
    });

    it('rejects expired signatures', function () {
        $media = MediaFactory::new()->createOne();
        Storage::disk($media->disk)->put($media->path, 'content');

        $expired = (string) Uri::temporarySignedRoute(
            'api.v1.media.file',
            now()->subMinute(),
            ['media' => $media->id],
        );

        $this->get($expired)->assertForbidden();
    });

    it('rejects requests without a signature', function () {
        $media = MediaFactory::new()->createOne();

        $this->get("/api/v1/media/{$media->id}/file")->assertForbidden();
    });

    it('returns 404 when the underlying file is missing', function () {
        $media = MediaFactory::new()->createOne();

        $url = app(MediaUrlResolver::class)->signed($media->id, 15);

        assertProblemResponse($this->get($url), 404, 'resource-not-found');
    });
});
