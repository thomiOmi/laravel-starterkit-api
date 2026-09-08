<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Console\Commands\MediaReprocessCommand;

covers(MediaReprocessCommand::class);

describe('media:reprocess', function () {
    beforeEach(function () {
        Storage::fake('public');
        Storage::fake('local');
        config(['media.queue' => false]);
    });

    it('regenerates a single named conversion', function () {
        $user = loginAsUser();

        $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 100))->toMediaCollection('avatars');
        $conversion = $media->conversions()->where('name', 'thumbnail')->firstOrFail();
        Storage::disk($conversion->disk)->delete($conversion->path);

        artisanCommand($this, 'media:reprocess', ['--conversion' => 'thumbnail'])->assertSuccessful();

        Storage::disk($conversion->disk)->assertExists($conversion->path);
    });

    it('fails when the named conversion is not defined', function () {
        $user = loginAsUser();

        $user->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 100))->toMediaCollection('avatars');

        artisanCommand($this, 'media:reprocess', ['--conversion' => 'missing'])->assertFailed();
    });
});
