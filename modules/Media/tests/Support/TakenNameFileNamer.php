<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use Modules\Media\Support\FileNamer\MediaFileNamer;

final class TakenNameFileNamer implements MediaFileNamer
{
    public function originalFileName(string $fileName): string
    {
        return 'taken.webp';
    }

    public function conversionFileName(string $fileName, string $conversion): string
    {
        return 'taken-'.$conversion.'.webp';
    }

    public function responsiveFileName(string $fileName): string
    {
        return $fileName;
    }
}
