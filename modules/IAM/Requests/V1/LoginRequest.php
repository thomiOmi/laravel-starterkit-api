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
            /**
             * User email address.
             *
             * @example jane@example.com
             */
            'email' => $this->emailRules(unique: false),
            /**
             * User password.
             *
             * @example password123
             */
            'password' => $this->passwordRules(confirmed: false, validate: false),
            /**
             * Client device label for token tracking.
             *
             * If not provided, the user agent string will be used as the device name.
             *
             * @example 'My iPhone'
             */
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function payload(): LoginPayload
    {
        return LoginPayload::fromRequest($this);
    }
}
