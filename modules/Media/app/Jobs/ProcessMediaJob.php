<?php

declare(strict_types=1);

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Actions\GenerateResponsiveImagesAction;
use Modules\Media\Events\MediaProcessed;
use Modules\Media\Events\MediaProcessingFailed;
use Modules\Media\Models\Media;
use Modules\Media\Services\MediaConversionService;
use Throwable;

/**
 * Generate conversions for a media item in the background.
 *
 * Retry policy stays below the queue connection's retry_after (90s by
 * default in config/queue.php): timeout 60s, three attempts with a
 * 10s/30s backoff ladder so a stuck worker is reclaimed before the
 * connection re-delivers the job.
 */
#[Backoff([10, 30])]
#[Timeout(60)]
#[Tries(3)]
final class ProcessMediaJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $mediaId,
    ) {}

    public function handle(MediaConversionService $service, GenerateResponsiveImagesAction $responsive): void
    {
        $startedAt = microtime(true);
        $media = Media::query()->find($this->mediaId);

        if ($media === null) {
            Log::info('Media processing skipped: media not found.', [
                'media_id' => $this->mediaId,
            ]);

            return;
        }

        try {
            $path = $media->getPath();

            throw_if(! is_string($path) || ! Storage::disk($media->disk)->exists($path), \RuntimeException::class, 'The source media file is missing.');

            $media->markProcessing();
            $service->generate($media);

            if (GenerateResponsiveImagesAction::wantsResponsive($media)) {
                $responsive->handle($media);
            }

            $media->markProcessed();
            event(new MediaProcessed($media));

            Log::info('Media processing completed.', [
                'media_id' => $media->id,
                'duration_ms' => $this->durationMs($startedAt),
            ]);
        } catch (Throwable $throwable) {
            $media->markProcessingFailed($throwable->getMessage());
            event(new MediaProcessingFailed($media, $throwable->getMessage()));

            Log::error('Media processing failed.', [
                'media_id' => $media->id,
                'duration_ms' => $this->durationMs($startedAt),
                'error' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
