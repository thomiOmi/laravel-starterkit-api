<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * User Request
 *
 * The request parameters for creating or updating a user.
 */
#[BodyParameter(name: 'name', description: 'The full name of the user.', required: true, example: 'John Doe')]
#[BodyParameter(name: 'email', description: 'The email address of the user. Must be unique.', required: true, example: 'john@example.com')]
#[BodyParameter(name: 'password', description: 'The password (min 8 characters). Required on create, optional on update.', example: 'password123')]
#[BodyParameter(name: 'password_confirmation', description: 'Password confirmation, must match password.', example: 'password123')]
class UserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Unique|ValidationRule>> The validation rules.
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                $this->isMethod('POST') ? 'required' : 'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }
}
