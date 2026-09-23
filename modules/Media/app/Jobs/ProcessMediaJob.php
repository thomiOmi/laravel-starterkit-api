<?php

declare(strict_types=1);

namespace Modules\Media\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
final class ProcessMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Attempt limit before the job is failed permanently.
     */
    public int $tries = 3;

    /**
     * Seconds allowed before the worker releases the job for another attempt.
     * Must stay strictly below the queue connection retry_after.
     */
    public int $timeout = 60;

    /**
     * Backoff between attempts, in seconds, keyed by attempt number.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30];

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

            if (! is_string($path) || ! Storage::disk($media->disk)->exists($path)) {
                throw new \RuntimeException('The source media file is missing.');
            }

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
        } catch (Throwable $exception) {
            $media->markProcessingFailed($exception->getMessage());
            event(new MediaProcessingFailed($media, $exception->getMessage()));

            Log::error('Media processing failed.', [
                'media_id' => $media->id,
                'duration_ms' => $this->durationMs($startedAt),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
