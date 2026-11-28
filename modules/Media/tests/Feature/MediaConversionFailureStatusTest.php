<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Actions\GenerateResponsiveImagesAction;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Events\MediaProcessingFailed;
use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Models\Media;
use Modules\Media\Services\MediaConversionService;

describe('processing status when conversions fail', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        Event::fake([MessageLogged::class, MediaProcessingFailed::class]);
    });

    it('marks a corrupt image as failed without retrying the job', function (): void {
        config(['queue.default' => 'database']);
        $user = loginAsUser();

        $valid = (string) UploadedFile::fake()->image('seed.png', 40, 40)->getContent();
        $truncated = UploadedFile::fake()->createWithContent('corrupt.png', substr($valid, 0, 40));
        $media = $user->addMedia($truncated)->toMediaCollection('avatars');

        dispatch(new ProcessMediaJob((string) $media->id));

        artisanCommand($this, 'queue:work', ['--once' => true])->assertSuccessful();

        expect(DB::table('jobs')->count())->toBe(0)
            ->and(DB::table('failed_jobs')->count())->toBe(0);

        $failed = $media->fresh();
        throw_unless($failed instanceof Media, RuntimeException::class, 'Failed media was not found.');

        expect($failed->processing_status->value)->toBe('failed')
            ->and($failed->processing_error)->toContain('medium');

        Event::assertDispatched(fn (MediaProcessingFailed $event): bool => $event->media->is($media));
    });

    it('keeps a valid image processed', function (): void {
        $user = loginAsUser();
        $media = $user->addMedia(UploadedFile::fake()->image('seed.png', 40, 40))->toMediaCollection('avatars');

        new ProcessMediaJob((string) $media->id)->handle(
            resolve(MediaConversionService::class),
            resolve(GenerateResponsiveImagesAction::class),
        );

        $processed = $media->fresh();
        throw_unless($processed instanceof Media, RuntimeException::class, 'Processed media was not found.');

        expect($processed->processing_status->value)->toBe('processed');
    });

    it('keeps media without configured conversions processed', function (): void {
        $media = MediaFactory::new()->createOne(['mime_type' => 'image/png']);
        Storage::disk($media->disk)->put($media->getPath() ?? '', (string) UploadedFile::fake()->image('seed.png', 40, 40)->getContent());

        new ProcessMediaJob((string) $media->id)->handle(
            resolve(MediaConversionService::class),
            resolve(GenerateResponsiveImagesAction::class),
        );

        $processed = $media->fresh();
        throw_unless($processed instanceof Media, RuntimeException::class, 'Processed media was not found.');

        expect($processed->processing_status->value)->toBe('processed');
    });
});
