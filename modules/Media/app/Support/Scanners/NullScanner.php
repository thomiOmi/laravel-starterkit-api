<?php

declare(strict_types=1);

namespace Modules\Media\Support\Scanners;

use Override;

/**
 * Default no-op malware scanner.
 */
final readonly class NullScanner implements MediaScanner
{
    #[Override]
    public function scan(string $realPath): void
    {
        //
    }
}
