<?php

declare(strict_types=1);

namespace Modules\Auth\Requests\V1;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Payloads\V1\RegisterPayload;

#[BodyParameter(name: 'name', description: 'Full name of the user.', required: true, example: 'John Doe')]
#[BodyParameter(name: 'email', description: 'Email address. Must be unique.', required: true, example: 'john@example.com')]
#[BodyParameter(name: 'password', description: 'Password (min 8 characters).', required: true, example: 'password123')]
#[BodyParameter(name: 'password_confirmation', description: 'Must match password.', required: true, example: 'password123')]
final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Password>>
     */
    public function rules(): array
    {
        $passwordRule = Password::defaults() ?? Password::min(8);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', $passwordRule, 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function payload(): RegisterPayload
    {
        return RegisterPayload::fromRequest($this);
    }
}
