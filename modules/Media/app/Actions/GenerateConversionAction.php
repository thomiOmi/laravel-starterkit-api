<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Modules\Media\Models\Media;
use Modules\Media\Models\MediaConversion;
use Modules\Media\Services\MediaConversionService;

/**
 * Generate a single named conversion for a media item.
 */
final readonly class GenerateConversionAction
{
    public function __construct(
        private MediaConversionService $conversionService,
    ) {}

    /**
     * @param  array{width?: int, height?: int, fit?: string, format?: string, quality?: int}|null  $config
     */
    public function handle(Media $media, string $conversion, ?array $config = null): MediaConversion
    {
        $cfg = $config ?? [];

        /** @var array{width?: int, height?: int, fit?: string, format?: string, quality?: int} $cfg */
        return $this->conversionService->generateOne($media, $conversion, $cfg);
    }
}
