<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use InvalidArgumentException;
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

    public function handle(Media $media, bool $queued = false, ?string $conversion = null): void
    {
        if ($conversion !== null && $conversion !== '') {
            $media->markProcessing();

            try {
                $generated = $this->conversionService->generateNamed($media, $conversion);
            } catch (\Throwable $exception) {
                $media->markProcessingFailed($exception->getMessage());

                throw $exception;
            }

            if ($generated === null) {
                $exception = new InvalidArgumentException("Conversion [{$conversion}] is not defined for this media.");
                $media->markProcessingFailed($exception->getMessage());

                throw $exception;
            }

            $media->markProcessed();

            return;
        }

        if ($queued || config()->boolean('media.queue', false)) {
            $media->markPending();
            ProcessMediaJob::dispatch($media->id);

            return;
        }

        $media->markProcessing();

        try {
            $this->conversionService->generate($media);
            $media->markProcessed();
        } catch (\Throwable $exception) {
            $media->markProcessingFailed($exception->getMessage());

            throw $exception;
        }
    }
}
