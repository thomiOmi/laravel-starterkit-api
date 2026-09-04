<?php

declare(strict_types=1);

namespace Modules\Media\Support\FileNamer;

final readonly class DefaultFileNamer implements MediaFileNamer
{
    public function originalFileName(string $fileName): string
    {
        return $fileName;
    }

    public function fileName(string $fileName): string
    {
        return $fileName;
    }
}
