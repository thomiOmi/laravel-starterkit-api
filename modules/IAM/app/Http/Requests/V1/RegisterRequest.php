<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Requests\V1;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Modules\IAM\Payloads\V1\RegisterPayload;

final class RegisterRequest extends FormRequest
{
    /**
     * Normalize the email before validation so the unique rule compares
     * case-insensitively across database drivers.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            if (is_string($email = $this->input('email'))) {
                $this->merge(['email' => strtolower(trim($email))]);
            }
        }
    }

    use PasswordValidationRules, ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Password|Unique|ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function payload(): RegisterPayload
    {
        return RegisterPayload::fromRequest($this);
    }
}
