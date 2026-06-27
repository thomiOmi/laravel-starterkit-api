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
     * @param  bool  $required  Whether the password is required.
     * @param  bool  $confirmed  Whether the password should be confirmed.
     * @param  bool  $validate  Whether the password should be validated against complexity rules.
     * @return array<int, Password|ValidationRule|string>
     */
    protected function passwordRules(bool $required = true, bool $confirmed = true, bool $validate = true): array
    {
        $rules = [$required ? 'required' : 'nullable', 'string', 'max:255'];

        if ($validate) {
            $rules[] = Password::defaults() ?? Password::min(8);
        }

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return array<int, string>
     */
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'max:255', 'current_password'];
    }
}
