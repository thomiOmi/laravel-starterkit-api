<?php

declare(strict_types=1);

namespace Modules\Auth\Requests\V1;

use App\Traits\Rules\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Payloads\V1\LoginPayload;

final class LoginRequest extends FormRequest
{
    use \App\Traits\Rules\ProfileValidationRules, PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Password|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'email' => $this->emailRules(unique: false),
            'password' => $this->passwordRules(confirmed: false, validate: false),
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function payload(): LoginPayload
    {
        return LoginPayload::fromRequest($this);
    }
}
