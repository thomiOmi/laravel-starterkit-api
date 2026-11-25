<?php

declare(strict_types=1);

namespace Modules\Media\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Modules\Media\Enums\MediaType;
use Modules\Media\Support\MediaMimeType;

/**
 * Applies the per-type upload limit, falling back to the global max_size.
 *
 * The type comes from the MIME detected from the file bytes, never from the
 * client-supplied MIME or extension. The size unit and the failure message
 * match Laravel's built-in `max` rule for files, so 422 responses keep the
 * same `validation.max.file` key as before.
 */
final class MaxFileSizeByType implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $realPath = $value->getRealPath();
        $detected = is_string($realPath) && $realPath !== ''
            ? MediaMimeType::detect($realPath)
            : null;

        $type = is_string($detected) && $detected !== ''
            ? MediaType::fromMime($detected)
            : null;

        $limit = $this->resolveLimit($type);

        // An invalid global limit is ignored (ADR-0010) rather than throwing.
        if ($limit === null) {
            return;
        }

        // Laravel's `max` rule compares getSize() / 1024 for uploaded files.
        if ($value->getSize() / 1024 > $limit) {
            $fail(__('validation.max.file', [
                'attribute' => $attribute,
                'max' => $limit,
            ]));
        }
    }

    private function resolveLimit(?MediaType $type): ?int
    {
        $configured = config('media.max_size_by_type');

        if ($type instanceof MediaType && is_array($configured)) {
            $candidate = $configured[$type->value] ?? null;

            if (is_int($candidate) && $candidate > 0) {
                return $candidate;
            }
        }

        $global = config('media.max_size');

        return is_int($global) && $global > 0 ? $global : null;
    }
}
