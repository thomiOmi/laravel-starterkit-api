<?php

declare(strict_types=1);

namespace Modules\Media\Http\Requests\V1;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Modules\Media\Payloads\V1\MediaUploadPayload;
use Modules\Media\Support\DisallowedExtensions;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|ValidationRule|Closure>>
     */
    public function rules(): array
    {
        return [
            /**
             * The uploaded file.
             */
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', array_values(array_filter(config()->array('media.mimes', []), is_string(...)))),
                'max:'.config()->integer('media.max_size'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value instanceof UploadedFile && DisallowedExtensions::contains($value->getClientOriginalName())) {
                        $fail(__('validation.media_disallowed_extension'));
                    }
                },
            ],
            /**
             * The logical collection to place the media in.
             *
             * @example avatars
             */
            'collection_name' => ['sometimes', 'string', 'max:50', 'alpha_dash'],
        ];
    }

    public function payload(): MediaUploadPayload
    {
        return MediaUploadPayload::fromRequest($this);
    }
}
