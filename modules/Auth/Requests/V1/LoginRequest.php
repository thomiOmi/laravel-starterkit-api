<?php

declare(strict_types=1);

namespace Modules\Auth\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Payloads\V1\LoginPayload;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
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

    public function payload(): LoginPayload
    {
        return LoginPayload::fromRequest($this);
    }
}
