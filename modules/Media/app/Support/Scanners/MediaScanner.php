<?php

declare(strict_types=1);

namespace Modules\Media\Support\Scanners;

/**
 * Malware scanning hook for uploads.
 *
 * Implementations inspect the real file bytes and throw
 * InvalidArgumentException when malware is detected. The default
 * NullScanner is a no-op; swap via the media.scanner config to wire
 * ClamAV or another engine without touching upload code.
 */
interface MediaScanner
{
    /**
     * @throws \InvalidArgumentException When the file looks malicious.
     */
    public function scan(string $realPath): void;
}
