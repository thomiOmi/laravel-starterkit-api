<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;

#[BodyParameter(name: 'email', description: 'User email address.', required: true, example: 'user@example.com')]
#[BodyParameter(name: 'password', description: 'User password.', required: true, example: 'password123')]
#[BodyParameter(name: 'device_name', description: 'Optional device name for the token.', required: false, example: 'my-iphone')]
class LoginRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
