<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Requests\V1;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class LogoutOtherDevicesRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'current_password' => $this->currentPasswordRules(),
        ];
    }
}
