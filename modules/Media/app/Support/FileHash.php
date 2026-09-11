<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

final class FileHash
{
    public static function hash(string $disk, string $path): ?string
    {
        try {
            $stream = Storage::disk($disk)->readStream($path);

            if (! is_resource($stream)) {
                return null;
            }

            try {
                $context = hash_init('sha256');
                hash_update_stream($context, $stream);

                return hash_final($context);
            } finally {
                fclose($stream);
            }
        } catch (Throwable) {
            return null;
        }
    }
}
