<?php

declare(strict_types=1);

namespace Modules\Auth\Requests\V1;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Payloads\V1\LoginPayload;

#[BodyParameter(name: 'email', description: 'User email address.', required: true, example: 'user@example.com')]
#[BodyParameter(name: 'password', description: 'User password.', required: true, example: 'password123')]
#[BodyParameter(name: 'device_name', description: 'Optional device name for the token.', required: false, example: 'my-iphone')]
final class LoginRequest extends FormRequest
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
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function payload(): LoginPayload
    {
        return LoginPayload::fromRequest($this);
    }
}
