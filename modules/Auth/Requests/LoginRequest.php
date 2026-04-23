<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Login Request
 *
 * The request parameters required to authenticate a user.
 */
#[BodyParameter(name: 'email', description: 'The email of the user.', required: true, example: 'user@example.com')]
#[BodyParameter(name: 'password', description: 'The password of the user.', required: true, example: 'password')]
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'password.required' => 'The password field is required.',
            'password.string' => 'The password must be a string.',
        ];
    }
}
