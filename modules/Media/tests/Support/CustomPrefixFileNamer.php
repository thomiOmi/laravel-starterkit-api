<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use Modules\Media\Support\FileNamer\MediaFileNamer;

final class CustomPrefixFileNamer implements MediaFileNamer
{
    public function originalFileName(string $fileName): string
    {
        return 'custom-'.$fileName;
    }

    public function conversionFileName(string $fileName, string $conversion): string
    {
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $converted = 'custom-'.$name.'-'.$conversion;

        return $extension !== '' ? $converted.'.'.$extension : $converted;
    }

    public function responsiveFileName(string $fileName): string
    {
        return $fileName;
    }
}
