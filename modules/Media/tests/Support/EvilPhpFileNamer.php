<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use Modules\Media\Support\FileNamer\MediaFileNamer;

final class EvilPhpFileNamer implements MediaFileNamer
{
    public function originalFileName(string $fileName): string
    {
        return 'evil.php';
    }

    public function conversionFileName(string $fileName, string $conversion): string
    {
        return 'evil-'.$conversion.'.php';
    }

    public function responsiveFileName(string $fileName): string
    {
        return $fileName;
    }
}
