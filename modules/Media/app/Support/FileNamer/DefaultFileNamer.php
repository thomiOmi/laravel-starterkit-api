<?php

declare(strict_types=1);

namespace Modules\Media\Support\FileNamer;

final readonly class DefaultFileNamer implements MediaFileNamer
{
    public function originalFileName(string $fileName): string
    {
        return $fileName;
    }

    public function conversionFileName(string $fileName, string $conversion): string
    {
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $converted = $name.'-'.$conversion;

        return $extension !== '' ? $converted.'.'.$extension : $converted;
    }

    public function responsiveFileName(string $fileName): string
    {
        return $fileName;
    }
}
