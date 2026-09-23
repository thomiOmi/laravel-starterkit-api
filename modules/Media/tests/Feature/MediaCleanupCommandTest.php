<?php

declare(strict_types=1);

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Console\Commands\MediaCleanupCommand;
use Modules\Media\Database\Factories\MediaFactory;

covers(MediaCleanupCommand::class);

describe('media:cleanup', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        Event::fake([MessageLogged::class]);
    });

    it('keeps responsive images, conversions, and the derived conversion cache', function (): void {
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
        Storage::disk('public')->put('conversions/derived/'.$media->id.'/w32-abcdef12.webp', 'variant');

        artisanCommand($this, 'media:cleanup')
            ->expectsOutputToContain('No orphan files found.')
            ->assertSuccessful();

        Storage::disk('public')->assertExists($media->getPath() ?? '');
        Storage::disk('public')->assertExists('default/responsive-images/320-photo.webp');
        Storage::disk('public')->assertExists('conversions/'.$media->id.'/thumbnail.webp');
        Storage::disk('public')->assertExists('conversions/derived/'.$media->id.'/w32-abcdef12.webp');
    });

    it('ignores foreign directories and deletes nothing by default', function (): void {
        $keeper = MediaFactory::new()->public()->inCollection('avatars')->createOne();
        Storage::disk('public')->put($keeper->getPath() ?? '', 'keeper');
        Storage::disk('public')->put('other-module/file.txt', 'foreign');
        Storage::disk('public')->put('avatars/orphan.webp', 'orphan');

        artisanCommand($this, 'media:cleanup')->assertSuccessful();

        Storage::disk('public')->assertExists($keeper->getPath() ?? '');
        Storage::disk('public')->assertExists('other-module/file.txt');
        Storage::disk('public')->assertExists('avatars/orphan.webp');
    });

    it('deletes real orphans only with force', function (): void {
        $keeper = MediaFactory::new()->public()->inCollection('avatars')->createOne();
        Storage::disk('public')->put($keeper->getPath() ?? '', 'keeper');
        Storage::disk('public')->put('avatars/orphan.webp', 'orphan');
        Storage::disk('public')->put('other-module/file.txt', 'foreign');

        artisanCommand($this, 'media:cleanup', ['--force' => true])->assertSuccessful();

        Storage::disk('public')->assertMissing('avatars/orphan.webp');
        Storage::disk('public')->assertExists($keeper->getPath() ?? '');
        Storage::disk('public')->assertExists('other-module/file.txt');
    });

    it('warns about database records with missing files', function (): void {
        $media = MediaFactory::new()->createOne();

        artisanCommand($this, 'media:cleanup')
            ->expectsOutputToContain('Missing file for media '.$media->id)
            ->assertSuccessful();
    });

    it('logs a structured warning when orphan files are found', function (): void {
        $keeper = MediaFactory::new()->public()->inCollection('avatars')->createOne();
        Storage::disk('public')->put($keeper->getPath() ?? '', 'keeper');
        Storage::disk('public')->put('avatars/orphan.webp', 'orphan');

        artisanCommand($this, 'media:cleanup')->assertSuccessful();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $event): bool => $event->level === 'warning'
            && str_contains((string) $event->message, 'Media orphan files detected.')
            && ($event->context['count'] ?? null) === 1
            && ($event->context['destructive'] ?? null) === false
            && ($event->context['files'] ?? null) === ['avatars/orphan.webp']);
    });

    it('logs a structured warning when database records are missing files', function (): void {
        $media = MediaFactory::new()->createOne();

        artisanCommand($this, 'media:cleanup')->assertSuccessful();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $event): bool => $event->level === 'warning'
            && str_contains((string) $event->message, 'Media records with missing files.')
            && ($event->context['count'] ?? null) === 1
            && ($event->context['media_ids'] ?? null) === [(string) $media->id]);
    });
});
