<?php

declare(strict_types=1);

namespace Modules\Media\Support;

/**
 * Guards file names against executable or scriptable extensions.
 *
 * Every dot-separated segment is inspected, so shell.php.jpg is
 * rejected even though its final extension looks harmless.
 *
 * The default list lives here so the shipped config and the in-code
 * fallback cannot drift; config/media.php references this static.
 */
final class DisallowedExtensions
{
    /** @var array<int, string> */
    public static array $defaultDisallowedExtensions = [
        'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8',
        'phtml', 'phtm', 'pht', 'phps', 'phar',
        'shtml', 'shtm', 'stm',
        'htaccess', 'htpasswd',
        'cgi', 'pl', 'asp', 'aspx', 'jsp', 'jspx',
        'exe', 'msi', 'com', 'bat', 'cmd', 'sh',
        'js', 'html', 'htm', 'xhtml', 'svg',
    ];

    public static function contains(string $filename): bool
    {
        $denied = self::deniedExtensions();

        if ($denied === []) {
            return false;
        }

        $segments = explode('.', strtolower(basename($filename)));
        array_shift($segments);

        foreach ($segments as $segment) {
            if ($segment !== '' && in_array($segment, $denied, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private static function deniedExtensions(): array
    {
        $configured = config('media.disallowed_extensions');

        $extensions = is_array($configured) ? $configured : self::$defaultDisallowedExtensions;

        $denied = [];

        foreach ($extensions as $extension) {
            if (is_string($extension) && $extension !== '') {
                $denied[] = ltrim(strtolower($extension), '.');
            }
        }

        return $denied;
    }
}
