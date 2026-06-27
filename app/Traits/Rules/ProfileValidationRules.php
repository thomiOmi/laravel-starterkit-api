<?php

declare(strict_types=1);

namespace App\Traits\Rules;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Modules\User\Models\User;

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
            $rules[] = $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId);
        }

        return $rules;
    }
}
