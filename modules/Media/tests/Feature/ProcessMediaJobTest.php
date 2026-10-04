<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Actions\GenerateResponsiveImagesAction;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Events\MediaProcessed;
use Modules\Media\Events\MediaProcessingFailed;
use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Models\Media;
use Modules\Media\Services\MediaConversionService;
use Modules\Media\Traits\InteractsWithMedia;

covers(ProcessMediaJob::class);

describe('ProcessMediaJob', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        config(['media.responsive.widths' => [32, 64]]);
        Event::fake([MessageLogged::class, MediaProcessed::class, MediaProcessingFailed::class]);
    });

    function jobOwner(): Model&HasMedia
    {
        $owner = new #[Table(name: 'users', key: 'id', keyType: 'string')] #[WithoutIncrementing] class extends Model implements HasMedia
        {
            use InteractsWithMedia;

            public function registerMediaCollections(): void
            {
                $this->addMediaCollection('gallery')->withResponsiveImages();
            }
        };

        $ownerId = (string) Str::ulid();

        DB::table('users')->insert([
            'id' => $ownerId,
            'name' => 'Job',
            'email' => 'job-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $owner->forceFill(['id' => $ownerId]);
        $owner->exists = true;

        return $owner;
    }

    it('configures a retry policy below the queue retry_after window', function (): void {
        $reflection = new ReflectionClass(ProcessMediaJob::class);
        $tries = $reflection->getAttributes(Tries::class)[0]->newInstance()->tries;
        $timeout = $reflection->getAttributes(Timeout::class)[0]->newInstance()->timeout;
        $backoff = $reflection->getAttributes(Backoff::class)[0]->newInstance()->backoff;

        expect($tries)->toBe(3)
            ->and($timeout)->toBe(60)
            ->and($backoff)->toBe([10, 30])
            ->and($timeout)->toBeLessThan((int) config('queue.connections.database.retry_after', 90));
    });

    it('logs duration and media id on success', function (): void {
        $user = loginAsUser();
        $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 40, 40))
            ->toMediaCollection('avatars');

        new ProcessMediaJob((string) $media->id)->handle(
            resolve(MediaConversionService::class),
            resolve(GenerateResponsiveImagesAction::class)
        );

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $event): bool => $event->level === 'info'
            && str_contains((string) $event->message, 'Media processing completed.')
            && ($event->context['media_id'] ?? null) === $media->id
            && isset($event->context['duration_ms'])
            && is_int($event->context['duration_ms'])
            && $event->context['duration_ms'] >= 0);
    });

    it('logs an error with duration and message when processing fails', function (): void {
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
                resolve(MediaConversionService::class),
                resolve(GenerateResponsiveImagesAction::class)
            );
        })->toThrow(RuntimeException::class);

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $event): bool => $event->level === 'error'
            && str_contains((string) $event->message, 'Media processing failed.')
            && ($event->context['media_id'] ?? null) === $media->id
            && ($event->context['error'] ?? null) === 'The source media file is missing.'
            && isset($event->context['duration_ms']));
    });

    it('logs and returns when the media row no longer exists', function (): void {
        new ProcessMediaJob('01MISSING')->handle(
            resolve(MediaConversionService::class),
            resolve(GenerateResponsiveImagesAction::class)
        );

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $event): bool => $event->level === 'info'
            && str_contains((string) $event->message, 'Media processing skipped: media not found.')
            && ($event->context['media_id'] ?? null) === '01MISSING');
    });

    it('dispatches MediaProcessed for the processed media', function (): void {
        $owner = jobOwner();
        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne(['mime_type' => 'image/jpeg']);
        Storage::disk($media->disk)->put($media->getPath() ?? '', (string) UploadedFile::fake()->image('seed.jpg', 100, 80)->getContent());

        new ProcessMediaJob((string) $media->id)->handle(
            resolve(MediaConversionService::class),
            resolve(GenerateResponsiveImagesAction::class)
        );

        Event::assertDispatched(MediaProcessed::class, fn (MediaProcessed $event): bool => $event->media->is($media));
    });

    it('dispatches MediaProcessingFailed with the failure reason', function (): void {
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
                resolve(MediaConversionService::class),
                resolve(GenerateResponsiveImagesAction::class)
            );
        })->toThrow(RuntimeException::class);

        Event::assertDispatched(MediaProcessingFailed::class, fn (MediaProcessingFailed $event): bool => $event->media->is($media)
            && $event->reason === 'The source media file is missing.');
    });

    it('stays idempotent when processing runs twice', function (): void {
        $owner = jobOwner();
        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne(['mime_type' => 'image/jpeg']);
        Storage::disk($media->disk)->put($media->getPath() ?? '', (string) UploadedFile::fake()->image('seed.jpg', 100, 80)->getContent());

        new ProcessMediaJob((string) $media->id)->handle(
            resolve(MediaConversionService::class),
            resolve(GenerateResponsiveImagesAction::class)
        );

        $first = $media->fresh();
        throw_unless($first instanceof Media, RuntimeException::class, 'Processed media was not found.');
        throw_unless(is_array($first->responsive_images), RuntimeException::class, 'Responsive images were not generated.');

        $afterFirstRun = array_keys($first->responsive_images);

        new ProcessMediaJob((string) $media->id)->handle(
            resolve(MediaConversionService::class),
            resolve(GenerateResponsiveImagesAction::class)
        );

        $second = $media->fresh();
        throw_unless($second instanceof Media, RuntimeException::class, 'Reprocessed media was not found.');
        throw_unless(is_array($second->responsive_images), RuntimeException::class, 'Responsive images were not generated on the second run.');

        expect($afterFirstRun)->not->toBeEmpty()
            ->and(array_keys($second->responsive_images))->toEqual($afterFirstRun)
            ->and($second->processing_status->value)->toBe('processed');
    });
});
