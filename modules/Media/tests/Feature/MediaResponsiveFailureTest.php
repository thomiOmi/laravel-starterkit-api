<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Actions\GenerateResponsiveImagesAction;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Models\Media;
use Modules\Media\Support\FileNamer\MediaFileNamer;

covers(GenerateResponsiveImagesAction::class);

describe('responsive image failure reporting', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        config(['media.responsive.widths' => [320]]);
    });

    it('logs an unreadable source image instead of silently skipping', function (): void {
        $warnings = [];

        Log::shouldReceive('warning')->andReturnUsing(
            function (string $message, array $context) use (&$warnings): void {
                $warnings[] = ['message' => $message, 'context' => $context];
            },
        );

        $media = MediaFactory::new()->createOne(['mime_type' => 'image/png']);
        Storage::disk($media->disk)->put($media->getPath() ?? '', 'not-a-real-image');

        $result = resolve(GenerateResponsiveImagesAction::class)->handle($media);

        expect($result)->toBeEmpty()
            ->and($warnings)->toHaveCount(1)
            ->and($warnings[0]['message'])->toBe('Media responsive image generation failed.')
            ->and($warnings[0]['context']['media_id'])->toBe($media->id)
            ->and($warnings[0]['context']['conversion'])->toBe('source')
            ->and($warnings[0]['context']['error'])->not->toBeEmpty();
    });

    it('logs a per-width failure and leaves the media unchanged', function (): void {
        $warnings = [];

        Log::shouldReceive('warning')->andReturnUsing(
            function (string $message, array $context) use (&$warnings): void {
                $warnings[] = ['message' => $message, 'context' => $context];
            },
        );

        $media = MediaFactory::new()->createOne([
            'mime_type' => 'image/png',
            'file_name' => 'photo.png',
            'original_extension' => 'png',
        ]);
        Storage::disk($media->disk)->put(
            $media->getPath() ?? '',
            (string) UploadedFile::fake()->image('photo.png', 640, 480)->getContent(),
        );

        $namer = Mockery::mock(MediaFileNamer::class);
        $namer->shouldReceive('responsiveFileName')->andThrow(new Exception('namer unavailable'));
        app()->instance(MediaFileNamer::class, $namer);

        $result = resolve(GenerateResponsiveImagesAction::class)->handle($media);

        expect($result)->toBeEmpty()
            ->and(Media::query()->findOrFail($media->id)->responsive_images)->toBeEmpty()
            ->and($warnings)->toHaveCount(1)
            ->and($warnings[0]['context']['conversion'])->toBe('320')
            ->and($warnings[0]['context']['error'])->toBe('namer unavailable');
    });

    it('still generates responsive siblings when every step succeeds', function (): void {
        $media = MediaFactory::new()->createOne([
            'mime_type' => 'image/png',
            'file_name' => 'photo.png',
            'original_extension' => 'png',
        ]);
        Storage::disk($media->disk)->put(
            $media->getPath() ?? '',
            (string) UploadedFile::fake()->image('photo.png', 640, 480)->getContent(),
        );

        $result = resolve(GenerateResponsiveImagesAction::class)->handle($media);

        expect($result)->toHaveKey(320)
            ->and(Media::query()->findOrFail($media->id)->responsive_images)->toHaveKey(320);
    });
});
