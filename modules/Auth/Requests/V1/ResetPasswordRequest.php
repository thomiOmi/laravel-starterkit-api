<?php

declare(strict_types=1);

namespace Modules\Auth\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
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
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', $passwordRule, 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
