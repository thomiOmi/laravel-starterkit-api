<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Modules\Media\Console\Commands\MediaCleanupCommand;
use Modules\Media\Database\Factories\MediaFactory;

covers(MediaCleanupCommand::class);

describe('media:cleanup', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('keeps responsive images, conversions, and the variant cache', function () {
        $media = MediaFactory::new()->public()->createOne([
            'responsive_images' => [320 => ['path' => 'default/responsive-images/320-photo.webp', 'size' => 10]],
        ]);
        Storage::disk('public')->put($media->getPath() ?? '', 'original');
        Storage::disk('public')->put('default/responsive-images/320-photo.webp', 'responsive');
        $media->conversions()->create([
            'name' => 'thumbnail',
            'disk' => 'public',
            'path' => 'conversions/'.$media->id.'/thumbnail.webp',
            'mime_type' => 'image/webp',
            'size' => 10,
            'etag' => 'test',
        ]);
        Storage::disk('public')->put('conversions/'.$media->id.'/thumbnail.webp', 'conversion');
        Storage::disk('public')->put('variants/'.$media->id.'/w32-abcdef12.webp', 'variant');

        artisanCommand($this, 'media:cleanup')
            ->expectsOutputToContain('No orphan files found.')
            ->assertSuccessful();

        Storage::disk('public')->assertExists($media->getPath() ?? '');
        Storage::disk('public')->assertExists('default/responsive-images/320-photo.webp');
        Storage::disk('public')->assertExists('conversions/'.$media->id.'/thumbnail.webp');
        Storage::disk('public')->assertExists('variants/'.$media->id.'/w32-abcdef12.webp');
    });

    it('ignores foreign directories and deletes nothing by default', function () {
        $keeper = MediaFactory::new()->public()->inCollection('avatars')->createOne();
        Storage::disk('public')->put($keeper->getPath() ?? '', 'keeper');
        Storage::disk('public')->put('other-module/file.txt', 'foreign');
        Storage::disk('public')->put('avatars/orphan.webp', 'orphan');

        artisanCommand($this, 'media:cleanup')->assertSuccessful();

        Storage::disk('public')->assertExists($keeper->getPath() ?? '');
        Storage::disk('public')->assertExists('other-module/file.txt');
        Storage::disk('public')->assertExists('avatars/orphan.webp');
    });

    it('deletes real orphans only with force', function () {
        $keeper = MediaFactory::new()->public()->inCollection('avatars')->createOne();
        Storage::disk('public')->put($keeper->getPath() ?? '', 'keeper');
        Storage::disk('public')->put('avatars/orphan.webp', 'orphan');
        Storage::disk('public')->put('other-module/file.txt', 'foreign');

        artisanCommand($this, 'media:cleanup', ['--force' => true])->assertSuccessful();

        Storage::disk('public')->assertMissing('avatars/orphan.webp');
        Storage::disk('public')->assertExists($keeper->getPath() ?? '');
        Storage::disk('public')->assertExists('other-module/file.txt');
    });

    it('warns about database records with missing files', function () {
        $media = MediaFactory::new()->createOne();

        artisanCommand($this, 'media:cleanup')
            ->expectsOutputToContain('Missing file for media '.$media->id)
            ->assertSuccessful();
    });
});
