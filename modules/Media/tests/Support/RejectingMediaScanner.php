<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Support;

use InvalidArgumentException;
use Modules\Media\Support\Scanners\MediaScanner;

final class RejectingMediaScanner implements MediaScanner
{
    public function scan(string $realPath): void
    {
        throw new InvalidArgumentException(__('validation.media_malware_detected'));
    }
}
