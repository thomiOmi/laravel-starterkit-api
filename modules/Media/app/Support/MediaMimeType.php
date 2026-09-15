<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use Symfony\Component\Mime\MimeTypes;
use Throwable;

/**
 * Content-based MIME sniffing for uploads.
 *
 * Uses finfo on the real file bytes, never the client-supplied mime.
 * Extension-to-mime consistency is checked via symfony/mime so a PHP
 * payload renamed to .png is rejected even though the extension looks
 * harmless.
 */
final class MediaMimeType
{
    /**
     * Content mimes that are never accepted regardless of extension.
     *
     * SVG is blocked because it can embed scripts; executable and markup
     * types are blocked even when the file extension looks innocent.
     *
     * @var array<int, string>
     */
    private const array BLOCKED_MIMES = [
        'application/x-php',
        'application/php',
        'text/x-php',
        'text/php',
        'application/x-sh',
        'application/x-bash',
        'text/x-shellscript',
        'application/x-msdownload',
        'application/x-ms-dos-executable',
        'application/vnd.microsoft.portable-executable',
        'application/x-phar',
        'text/html',
        'application/xhtml+xml',
        'application/javascript',
        'text/javascript',
        'application/x-javascript',
        'image/svg+xml',
    ];

    public static function detect(string $realPath): ?string
    {
        try {
            $info = finfo_open(FILEINFO_MIME_TYPE);

            if ($info === false) {
                return null;
            }

            try {
                $mime = finfo_file($info, $realPath);
            } finally {
                finfo_close($info);
            }

            return is_string($mime) && $mime !== '' ? strtolower($mime) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function detectFromContent(string $content): ?string
    {
        try {
            $info = finfo_open(FILEINFO_MIME_TYPE);

            if ($info === false) {
                return null;
            }

            try {
                $mime = finfo_buffer($info, $content);
            } finally {
                finfo_close($info);
            }

            return is_string($mime) && $mime !== '' ? strtolower($mime) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function isBlocked(string $mime): bool
    {
        return in_array(strtolower($mime), self::BLOCKED_MIMES, true);
    }

    public static function isImage(string $mime): bool
    {
        return str_starts_with(strtolower($mime), 'image/');
    }

    /**
     * Check that the file extension is consistent with the sniffed mime.
     *
     * Unknown extensions or mimes fail open (defer to the allowlist and
     * collection rules); only a positive mismatch is rejected.
     */
    public static function extensionMatchesMime(string $extension, string $mime): bool
    {
        $extension = ltrim(strtolower($extension), '.');
        $mime = strtolower($mime);

        if ($extension === '' || $mime === '') {
            return false;
        }

        try {
            $mimes = MimeTypes::getDefault()->getMimeTypes($extension);
        } catch (Throwable) {
            return true;
        }

        if ($mimes === []) {
            return true;
        }

        if (in_array($mime, array_map(strtolower(...), $mimes), true)) {
            return true;
        }

        try {
            $extensions = MimeTypes::getDefault()->getExtensions($mime);
        } catch (Throwable) {
            return true;
        }

        if ($extensions === []) {
            return true;
        }

        return in_array($extension, array_map(fn ($ext): string => ltrim(strtolower((string) $ext), '.'), $extensions), true);
    }
}
