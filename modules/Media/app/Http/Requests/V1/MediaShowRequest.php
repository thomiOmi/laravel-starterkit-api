<?php

declare(strict_types=1);

namespace Modules\Media\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MediaShowRequest extends FormRequest
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
             * When present, the returned url is a temporary signed link
             * valid for this many minutes (1..1440).
             *
             * @example 15
             */
            'expires' => ['sometimes', 'integer', 'between:1,1440'],
        ];
    }

    /**
     * The requested signed-url lifetime in minutes, or null when the
     * caller did not ask for one.
     */
    public function expiresMinutes(): ?int
    {
        if (! $this->filled('expires')) {
            return null;
        }

        return $this->integer('expires');
    }
}
