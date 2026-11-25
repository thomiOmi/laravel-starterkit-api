<?php

declare(strict_types=1);

namespace Modules\Media\Enums;

/**
 * Coarse classification derived from the MIME detected from file contents.
 *
 * The values are the strings exposed on the MediaResource `type` field. They
 * are intentionally broad: a starter kit needs to tell documents, video, and
 * audio apart without per-format processing.
 */
enum MediaType: string
{
    case Image = 'image';
    case Document = 'document';
    case Video = 'video';
    case Audio = 'audio';
    case Other = 'other';

    /**
     * Document MIME families, including the values finfo returns for legacy
     * Office binaries and ZIP-based Office/OpenDocument files.
     *
     * @var list<string>
     */
    private const array DOCUMENT_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // @pest-ignore-profanity
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
        'application/x-ole-storage',
        'application/rtf',
        'text/rtf',
        'text/plain',
        'text/csv',
    ];

    /**
     * Normalize a MIME string and map it to a coarse media type.
     *
     * Parameters such as `charset=utf-8` are ignored, and casing is not
     * significant. Unknown and empty values fall through to `Other` so that
     * the classification never invents a type for content it cannot name.
     */
    public static function fromMime(string $mime): self
    {
        $normalized = strtolower(trim(explode(';', $mime, 2)[0]));

        if (str_starts_with($normalized, 'image/')) {
            return self::Image;
        }

        if (str_starts_with($normalized, 'video/')) {
            return self::Video;
        }

        if (str_starts_with($normalized, 'audio/')) {
            return self::Audio;
        }

        if (in_array($normalized, self::DOCUMENT_MIMES, true)) {
            return self::Document;
        }

        return self::Other;
    }
}
