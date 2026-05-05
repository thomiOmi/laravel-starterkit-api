<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

#[BodyParameter(name: 'name', description: 'Full name of the user.', required: true, example: 'John Doe')]
#[BodyParameter(name: 'email', description: 'Email address. Must be unique.', required: true, example: 'john@example.com')]
#[BodyParameter(name: 'password', description: 'Password (min 8 characters).', required: true, example: 'password123')]
#[BodyParameter(name: 'password_confirmation', description: 'Must match password.', required: true, example: 'password123')]
class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
