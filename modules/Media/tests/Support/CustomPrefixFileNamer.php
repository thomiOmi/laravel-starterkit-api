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
        return 'custom-'.$conversion.'.webp';
    }

    public function responsiveFileName(string $fileName): string
    {
        return $fileName;
    }
}
