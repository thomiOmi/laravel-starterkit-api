<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Actions\GenerateResponsiveImagesAction;
use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Models\Media;
use Modules\Media\Services\MediaConversionService;

covers(ProcessMediaJob::class);

describe('ProcessMediaJob', function () {
    beforeEach(function () {
        Storage::fake('public');
        Storage::fake('local');
        Event::fake([MessageLogged::class]);
    });

    it('configures a retry policy below the queue retry_after window', function () {
        $job = new ProcessMediaJob('01TEST');

        expect($job->tries)->toBe(3)
            ->and($job->timeout)->toBe(60)
            ->and($job->backoff)->toBe([10, 30])
            ->and($job->timeout)->toBeLessThan((int) config('queue.connections.database.retry_after', 90));
    });

    it('logs duration and media id on success', function () {
        $user = loginAsUser();
        $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 40, 40))
            ->toMediaCollection('avatars');

        new ProcessMediaJob((string) $media->id)->handle(
            app(MediaConversionService::class),
            app(GenerateResponsiveImagesAction::class)
        );

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $event): bool => $event->level === 'info'
            && str_contains((string) $event->message, 'Media processing completed.')
            && ($event->context['media_id'] ?? null) === $media->id
            && isset($event->context['duration_ms'])
            && is_int($event->context['duration_ms'])
            && $event->context['duration_ms'] >= 0);
    });

    it('logs an error with duration and message when processing fails', function () {
        $media = Media::query()->create([
            'collection_name' => 'default',
            'name' => 'missing',
            'file_name' => 'missing.jpg',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 10,
            'visibility' => 'private',
            'processing_status' => 'pending',
        ]);

        expect(function () use ($media): void {
            new ProcessMediaJob((string) $media->id)->handle(
                app(MediaConversionService::class),
                app(GenerateResponsiveImagesAction::class)
            );
        })->toThrow(RuntimeException::class);

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $event): bool => $event->level === 'error'
            && str_contains((string) $event->message, 'Media processing failed.')
            && ($event->context['media_id'] ?? null) === $media->id
            && ($event->context['error'] ?? null) === 'The source media file is missing.'
            && isset($event->context['duration_ms']));
    });

    it('logs and returns when the media row no longer exists', function () {
        new ProcessMediaJob('01MISSING')->handle(
            app(MediaConversionService::class),
            app(GenerateResponsiveImagesAction::class)
        );

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $event): bool => $event->level === 'info'
            && str_contains((string) $event->message, 'Media processing skipped: media not found.')
            && ($event->context['media_id'] ?? null) === '01MISSING');
    });
});
