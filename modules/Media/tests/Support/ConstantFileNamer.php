<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use Modules\Media\Support\FileNamer\MediaFileNamer;

final class ConstantFileNamer implements MediaFileNamer
{
    public function originalFileName(string $fileName): string
    {
        return 'fixed.pdf';
    }

    public function conversionFileName(string $fileName, string $conversion): string
    {
        return 'fixed-'.$conversion.'.pdf';
    }

    public function responsiveFileName(string $fileName): string
    {
        return $fileName;
    }
}
