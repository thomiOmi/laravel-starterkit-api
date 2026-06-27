<?php

declare(strict_types=1);

namespace Modules\Auth\Requests\V1;

use App\Traits\Rules\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Password|ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => $this->passwordRules(),
        ];
    }
}
