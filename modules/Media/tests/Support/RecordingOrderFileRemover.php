<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use Illuminate\Support\Facades\DB;
use Modules\Media\Models\Media;
use Modules\Media\Support\FileRemover\MediaFileRemover;
use Modules\Media\Support\FileRemover\MediaFilesSnapshot;
use RuntimeException;

final class RecordingOrderFileRemover implements MediaFileRemover
{
    /** @var list<array{media_id: string, row_exists: bool, transaction_level: int}> */
    public static array $observations = [];

    /**
     * @return array{media_id: string, row_exists: bool, transaction_level: int}
     */
    public static function first(): array
    {
        return self::$observations[0] ?? throw new RuntimeException('No file removal was observed.');
    }

    public function removeAllFiles(Media $media): void
    {
        self::$observations[] = [
            'media_id' => (string) $media->id,
            'row_exists' => Media::query()->whereKey($media->id)->exists(),
            'transaction_level' => DB::transactionLevel(),
        ];
    }

    public function removeCapturedFiles(MediaFilesSnapshot $snapshot): void
    {
        self::$observations[] = [
            'media_id' => $snapshot->mediaId,
            'row_exists' => Media::query()->whereKey($snapshot->mediaId)->exists(),
            'transaction_level' => DB::transactionLevel(),
        ];
    }

    public function removeResponsiveImages(Media $media): void
    {
        // No-op for the recording stub.
    }

    public function removeFile(string $path, string $disk): void
    {
        // No-op for the recording stub.
    }
}
