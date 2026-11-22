<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Requests\V1;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rules\Confirmed;
use Illuminate\Validation\Rules\CurrentPassword;
use Illuminate\Validation\Rules\Password;
use Modules\IAM\Payloads\V1\ChangePasswordPayload;

final class ChangePasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * @return array<string, array<int, ConditionalRules|Confirmed|CurrentPassword|Password|ValidationRule|string>>
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
