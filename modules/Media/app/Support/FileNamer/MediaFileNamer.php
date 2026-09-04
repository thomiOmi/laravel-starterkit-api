<?php

declare(strict_types=1);

namespace Modules\Media\Support\FileNamer;

interface MediaFileNamer
{
    public function originalFileName(string $fileName): string;

    public function conversionFileName(string $fileName, string $conversion): string;

    public function responsiveFileName(string $fileName): string;
}
