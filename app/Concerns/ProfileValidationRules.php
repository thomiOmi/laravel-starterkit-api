<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @param  string|null  $userId  The user ID to ignore for unique validation.
     * @param  bool  $unique  Whether the email should be unique.
     * @return array<string, array<int, string|Unique>>
     */
    protected function profileRules(?string $userId = null, bool $unique = true): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId, $unique),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @param  string|null  $userId  The user ID to ignore for unique validation.
     * @param  bool  $unique  Whether the email should be unique.
     * @return array<int, string|Unique>
     */
    protected function emailRules(?string $userId = null, bool $unique = true): array
    {
        $rules = ['required', 'string', 'email', 'max:255'];

        if ($unique) {
            $model = config()->string('auth.providers.users.model');

            $rules[] = $userId === null
                ? Rule::unique($model)
                : Rule::unique($model)->ignore($userId);
        }

        return $rules;
    }
}
