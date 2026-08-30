<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Models\Media;
use Modules\Media\Services\MediaConversionService;

/**
 * Re-generate conversions for a media item, either synchronously or queued.
 */
final readonly class ReprocessMediaAction
{
    public function __construct(
        private MediaConversionService $conversionService,
    ) {}

    public function handle(Media $media, bool $queued = false): void
    {
        if ($queued || config()->boolean('media.queue', false)) {
            ProcessMediaJob::dispatch($media->id);

            return;
        }

        $this->conversionService->generate($media);
    }
}
