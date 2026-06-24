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
     * @return array<string, array<int, string|Unique>>
     */
    protected function profileRules(?string $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
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
     * @return array<int, string|Unique>
     */
    protected function emailRules(?string $userId = null): array
    {
        return [
            ...$this->baseEmailRules(),
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * Get the base validation rules for emails.
     *
     * @return array<int, string>
     */
    protected function baseEmailRules(): array
    {
        return ['required', 'string', 'email', 'max:255'];
    }
}
