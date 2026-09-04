<?php

declare(strict_types=1);

namespace Modules\Media\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
            $service->generate($media);

            if (GenerateResponsiveImagesAction::wantsResponsive($media)) {
                $responsive->handle($media);
            }

            event(new MediaProcessed($media));
        } catch (Throwable $e) {
            event(new MediaProcessingFailed($media, $e->getMessage()));

            throw $e;
        }
    }
}
