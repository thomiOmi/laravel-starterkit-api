<?php

declare(strict_types=1);

namespace Modules\Auth\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Payloads\V1\RegisterPayload;

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
            'password' => ['required', 'string', 'max:255', $passwordRule, 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function payload(): RegisterPayload
    {
        return RegisterPayload::fromRequest($this);
    }
}
