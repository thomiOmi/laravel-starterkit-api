<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Modules\IAM\Payloads\V1\LoginPayload;

final class LoginRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Password|ValidationRule|Unique>>
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
