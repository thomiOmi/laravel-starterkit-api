<?php

declare(strict_types=1);

namespace Modules\Media\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Modules\Media\Support\DisallowedExtensions;

final class AllowedFileName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        if (DisallowedExtensions::contains($value->getClientOriginalName())) {
            $fail(__('validation.media_disallowed_extension'));
        }
    }
}
