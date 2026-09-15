<?php

declare(strict_types=1);

namespace Modules\Media\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Modules\Media\Support\MediaMimeType;

/**
 * Rejects files whose sniffed content mime is blocked or mismatches
 * the file extension (e.g. PHP source renamed to .png).
 */
final class DetectedMimeType implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $realPath = $value->getRealPath();

        if (! is_string($realPath) || $realPath === '') {
            $fail(__('validation.media_mime_mismatch'));

            return;
        }

        $detected = MediaMimeType::detect($realPath);

        if ($detected === null) {
            $fail(__('validation.media_mime_mismatch'));

            return;
        }

        if (MediaMimeType::isBlocked($detected)) {
            $fail(__('validation.media_disallowed_extension'));

            return;
        }

        $extension = strtolower(pathinfo($value->getClientOriginalName(), PATHINFO_EXTENSION));

        if ($extension !== '' && ! MediaMimeType::extensionMatchesMime($extension, $detected)) {
            $fail(__('validation.media_mime_mismatch'));
        }
    }
}
