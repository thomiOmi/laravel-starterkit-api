<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Confirmed;
use Illuminate\Validation\Rules\CurrentPassword;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * Rules are ordered cheapest first and prefixed with `bail` so that a
     * failure skips the expensive rules (including the `uncompromised`
     * request made by the production password defaults).
     *
     * @param  bool  $required  Whether the password is required.
     * @param  bool  $confirmed  Whether the password should be confirmed.
     * @param  bool  $validate  Whether the password should be validated against complexity rules.
     * @return array<int, ConditionalRules|Confirmed|Password|ValidationRule|string>
     */
    protected function passwordRules(bool $required = true, bool $confirmed = true, bool $validate = true): array
    {
        $rules = [
            'bail',
            $required ? 'required' : 'nullable',
            'string',
            'max:255',
        ];

        if ($confirmed) {
            $rules[] = Rule::confirmed();
        }

        $rules[] = Rule::when($validate, [Password::defaults()]);

        return $rules;
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return array<int, CurrentPassword|ValidationRule|string>
     */
    protected function currentPasswordRules(): array
    {
        return ['bail', 'required', 'string', 'max:255', Rule::currentPassword()];
    }
}
