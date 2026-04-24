<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Register Request
 *
 * The request parameters required to register a new user account.
 */
#[BodyParameter(name: 'name', description: 'The full name of the user.', required: true, example: 'John Doe')]
#[BodyParameter(name: 'email', description: 'The email address of the user.', required: true, example: 'john@example.com')]
#[BodyParameter(name: 'password', description: 'The password for the account (min 8 characters).', required: true, example: 'password123')]
#[BodyParameter(name: 'password_confirmation', description: 'Password confirmation, must match password.', required: true, example: 'password123')]
class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
