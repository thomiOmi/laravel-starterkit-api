<?php

declare(strict_types=1);

namespace App\Traits\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Password|ValidationRule|string>
     */
    protected function passwordRules(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'max:255', Password::defaults() ?? Password::min(8), 'confirmed'];
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return array<int, string>
     */
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'current_password'];
    }
}
