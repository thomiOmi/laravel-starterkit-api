<?php

declare(strict_types=1);

namespace Modules\Media\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MediaVariantRequest extends FormRequest
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
        return [
            /**
             * The maximum width of the returned variant in pixels.
             *
             * @example 512
             */
            'w' => ['required', 'integer', 'between:32,2000'],

            /**
             * The output format of the returned variant.
             *
             * @example webp
             */
            'format' => ['sometimes', 'string', 'in:webp,jpg'],
        ];
    }

    /**
     * The validated variant width. Validation guarantees the 32..2000
     * bounds, so this is always a positive integer.
     *
     * @return int<1, max>
     */
    public function width(): int
    {
        $width = $this->integer('w');

        if ($width < 1) {
            return 1;
        }

        return $width;
    }
}
