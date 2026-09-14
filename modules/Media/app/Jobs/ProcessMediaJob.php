<?php

declare(strict_types=1);

namespace Modules\Media\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Actions\GenerateResponsiveImagesAction;
use Modules\Media\Events\MediaProcessed;
use Modules\Media\Events\MediaProcessingFailed;
use Modules\Media\Models\Media;
use Modules\Media\Services\MediaConversionService;
use Throwable;

/**
 * Generate conversions for a media item in the background.
 */
final class ProcessMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $mediaId,
    ) {}

    public function handle(MediaConversionService $service, GenerateResponsiveImagesAction $responsive): void
    {
        $media = Media::query()->find($this->mediaId);

        if ($media === null) {
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
        } catch (Throwable $exception) {
            $media->markProcessingFailed($exception->getMessage());
            event(new MediaProcessingFailed($media, $exception->getMessage()));

            throw $exception;
        }
    }
}
