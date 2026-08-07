<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\IAM\Payloads\V1\ChangePasswordPayload;

final class ChangePasswordRequest extends FormRequest
{
    use PasswordValidationRules;

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
            /**
             * The current password of the authenticated user.
             *
             * @example current-password-123
             */
            'current_password' => ['required', 'string', 'max:255'],
            /**
             * The new password.
             *
             * @example new-password-123
             */
            'password' => $this->passwordRules(),
        ];
    }

    public function payload(): ChangePasswordPayload
    {
        return ChangePasswordPayload::fromRequest($this);
    }
}
