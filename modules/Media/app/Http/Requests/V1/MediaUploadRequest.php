<?php

declare(strict_types=1);

namespace Modules\Media\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Media\Payloads\V1\MediaUploadPayload;
use Modules\Media\Rules\AllowedFileName;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        $allowed = [];
        $configured = config('media.allowed_extensions');

        if (is_array($configured)) {
            foreach ($configured as $extension) {
                if (is_string($extension) && $extension !== '') {
                    $allowed[] = ltrim(strtolower($extension), '.');
                }
            }
        }

        $fileRules = [
            'required',
            'file',
            'max:'.config()->integer('media.max_size'),
            new AllowedFileName,
        ];

        if ($allowed !== []) {
            $fileRules[] = 'extensions:'.implode(',', $allowed);
        }

        return [
            /**
             * The uploaded file.
             */
            'file' => $fileRules,
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
