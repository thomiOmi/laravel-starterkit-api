<?php

declare(strict_types=1);

namespace Modules\ApiKey\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApiKeyRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'ip_whitelist' => 'nullable|array',
            'ip_whitelist.*' => 'ip',
            'expires_at' => 'nullable|date|after:now',
        ];
    }
}
