<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'array'],
            'page.size' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
            'page.number' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
