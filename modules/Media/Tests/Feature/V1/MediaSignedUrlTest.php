<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Modules\Media\Database\Factories\MediaFactory;

describe('Media signed url access', function (): void {
    afterEach(function (): void {
        Storage::disk('local')->deleteDirectory('media');
    });

    it('serves a private file with a valid signature', function (): void {
        $media = MediaFactory::new()->createOne(['disk' => 'local']);
        Storage::disk('local')->put($media->path, 'secret content');

        $signedUrl = Storage::disk('local')->temporaryUrl($media->path, now()->addMinutes(15));

        $this->get($signedUrl)->assertOk();

        expect(Storage::disk('local')->get($media->path))->toBe('secret content');
    })->group('module:media');

    it('rejects a private file with an expired signature', function (): void {
        $media = MediaFactory::new()->createOne(['disk' => 'local']);
        Storage::disk('local')->put($media->path, 'secret content');

        $expiredUrl = Storage::disk('local')->temporaryUrl($media->path, now()->subMinutes(5));

        $this->get($expiredUrl)
            ->assertForbidden();
    })->group('module:media');

    it('returns 404 when the file does not exist', function (): void {
        $media = MediaFactory::new()->createOne(['disk' => 'local']);

        $signedUrl = Storage::disk('local')->temporaryUrl($media->path, now()->addMinutes(15));

        $this->get($signedUrl)
            ->assertNotFound();
    })->group('module:media');
});
