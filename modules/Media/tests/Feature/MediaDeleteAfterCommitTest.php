<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaConversion;
use Modules\Media\Observers\MediaObserver;
use Modules\Media\Support\FileRemover\MediaFileRemover;
use Modules\Media\Tests\Support\RecordingOrderFileRemover;
use Modules\Media\Tests\Support\ThrowingFileRemover;

covers(MediaObserver::class);

describe('media delete after commit', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
    });

    it('removes the file after delete without an explicit transaction', function (): void {
        $media = MediaFactory::new()->createOne();
        $path = $media->getPath() ?? '';
        Storage::disk($media->disk)->put($path, 'original');

        $media->delete();

        expect(Media::query()->whereKey($media->id)->exists())->toBeFalse();
        Storage::disk($media->disk)->assertMissing($path);
    });

    it('keeps the row and the file when the transaction rolls back', function (): void {
        config(['media.file_remover' => RecordingOrderFileRemover::class]);
        app()->forgetInstance(MediaFileRemover::class);
        RecordingOrderFileRemover::$observations = [];

        $media = MediaFactory::new()->createOne();
        $path = $media->getPath() ?? '';
        Storage::disk($media->disk)->put($path, 'original');

        try {
            DB::transaction(function () use ($media): void {
                $media->delete();

                throw new Exception('rollback for test');
            });
        } catch (Exception) {
            // Expected: the enclosing transaction is rolled back.
        }

        expect(Media::query()->whereKey($media->id)->exists())->toBeTrue()
            ->and(RecordingOrderFileRemover::$observations)->toBeEmpty();
        Storage::disk($media->disk)->assertExists($path);
    });

    it('removes the file only after the transaction commits', function (): void {
        $media = MediaFactory::new()->createOne();
        $path = $media->getPath() ?? '';
        Storage::disk($media->disk)->put($path, 'original');

        DB::transaction(function () use ($media, $path): void {
            $media->delete();

            expect(Media::query()->whereKey($media->id)->exists())->toBeFalse()
                ->and(Storage::disk($media->disk)->exists($path))->toBeTrue();
        });

        Storage::disk($media->disk)->assertMissing($path);
    });

    it('removes originals stored on the private disk', function (): void {
        $media = MediaFactory::new()->createOne([
            'disk' => 'local',
            'conversions_disk' => 'local',
        ]);
        $path = $media->getPath() ?? '';
        Storage::disk('local')->put($path, 'original');

        $media->delete();

        Storage::disk('local')->assertMissing($path);
    });

    it('logs storage failures with media id, path and disk without throwing', function (): void {
        $warnings = [];

        Log::shouldReceive('warning')->andReturnUsing(
            function (string $message, array $context) use (&$warnings): void {
                $warnings[] = ['message' => $message, 'context' => $context];
            },
        );

        config(['media.file_remover' => ThrowingFileRemover::class]);
        app()->forgetInstance(MediaFileRemover::class);

        $media = MediaFactory::new()->createOne();
        $path = $media->getPath() ?? '';

        ThrowingFileRemover::$calls = 0;

        expect(fn (): ?bool => $media->delete())->not->toThrow(Throwable::class)
            ->and(ThrowingFileRemover::$calls)->toBe(1)
            ->and($warnings)->toHaveCount(1)
            ->and($warnings[0]['message'])->toBe('Media file deletion failed.')
            ->and($warnings[0]['context']['media_id'])->toBe((string) $media->id)
            ->and($warnings[0]['context']['path'])->toBe($path)
            ->and($warnings[0]['context']['disk'])->toBe($media->disk)
            ->and($warnings[0]['context']['error'])->toBe('storage down');
    });

    it('removes conversions and responsive files even though their rows cascade', function (): void {
        $media = MediaFactory::new()->createOne([
            'conversions_disk' => 'local',
            'responsive_images' => [320 => ['path' => 'default/responsive-images/320-photo.png', 'size' => 10]],
        ]);
        $original = $media->getPath() ?? '';
        $conversion = 'conversions/'.$media->id.'/thumbnail.webp';
        $responsive = 'default/responsive-images/320-photo.png';
        $derived = 'conversions/derived/'.$media->id;

        Storage::disk('public')->put($original, 'original');
        Storage::disk('local')->put($conversion, 'conversion');
        Storage::disk('local')->put($responsive, 'responsive');
        Storage::disk('local')->put($derived.'/w32-abcdef12.webp', 'variant');

        $media->conversions()->create([
            'name' => 'thumbnail',
            'disk' => 'local',
            'path' => $conversion,
            'mime_type' => 'image/webp',
            'size' => 10,
            'etag' => 'test',
        ]);

        $media->delete();

        expect(MediaConversion::query()->where('media_id', $media->id)->exists())->toBeFalse();
        Storage::disk('public')->assertMissing($original);
        Storage::disk('local')->assertMissing($conversion);
        Storage::disk('local')->assertMissing($responsive);
        Storage::disk('local')->assertMissing($derived);
    });

    it('removes files only after the row is gone without a transaction', function (): void {
        config(['media.file_remover' => RecordingOrderFileRemover::class]);
        app()->forgetInstance(MediaFileRemover::class);
        RecordingOrderFileRemover::$observations = [];

        $media = MediaFactory::new()->createOne();
        $media->delete();

        $observation = RecordingOrderFileRemover::first();

        expect(RecordingOrderFileRemover::$observations)->toHaveCount(1)
            ->and($observation['media_id'])->toBe((string) $media->id)
            ->and($observation['row_exists'])->toBeFalse()
            ->and($observation['transaction_level'])->toBe(1);
    });

    it('keeps the file when deleting is cancelled', function (): void {
        $media = MediaFactory::new()->createOne([
            'conversions_disk' => 'local',
            'responsive_images' => [320 => ['path' => 'default/responsive-images/320-photo.png', 'size' => 10]],
        ]);
        $path = $media->getPath() ?? '';
        $conversion = 'conversions/'.$media->id.'/thumbnail.webp';
        $responsive = 'default/responsive-images/320-photo.png';
        $derived = 'conversions/derived/'.$media->id;

        Storage::disk($media->disk)->put($path, 'original');
        Storage::disk('local')->put($conversion, 'conversion');
        Storage::disk('local')->put($responsive, 'responsive');
        Storage::disk('local')->put($derived.'/w32-abcdef12.webp', 'variant');

        $media->conversions()->create([
            'name' => 'thumbnail',
            'disk' => 'local',
            'path' => $conversion,
            'mime_type' => 'image/webp',
            'size' => 10,
            'etag' => 'test',
        ]);

        Media::observe(new class
        {
            public function deleting(Media $media): bool
            {
                return false;
            }
        });

        try {
            expect($media->delete())->toBeFalse()
                ->and(Media::query()->whereKey($media->id)->exists())->toBeTrue();

            Storage::disk($media->disk)->assertExists($path);
            Storage::disk('local')->assertExists($conversion);
            Storage::disk('local')->assertExists($responsive);
            Storage::disk('local')->assertExists($derived);
        } finally {
            Media::flushEventListeners();
            Media::observe(new MediaObserver);
        }
    });

    it('waits for commit when deleting inside a transaction', function (): void {
        config(['media.file_remover' => RecordingOrderFileRemover::class]);
        app()->forgetInstance(MediaFileRemover::class);
        RecordingOrderFileRemover::$observations = [];

        $media = MediaFactory::new()->createOne();

        DB::transaction(function () use ($media): void {
            $media->delete();

            expect(RecordingOrderFileRemover::$observations)->toBeEmpty();
        });

        $observation = RecordingOrderFileRemover::first();

        expect(RecordingOrderFileRemover::$observations)->toHaveCount(1)
            ->and($observation['row_exists'])->toBeFalse()
            ->and($observation['transaction_level'])->toBe(1);
    });
});
