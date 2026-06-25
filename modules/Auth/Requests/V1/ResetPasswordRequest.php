<?php

declare(strict_types=1);

namespace Modules\Auth\Requests\V1;

use App\Traits\Rules\PasswordValidationRules;
use App\Traits\Rules\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

final class ResetPasswordRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => $this->emailExistsRules(),
            'password' => $this->passwordRules(),
        ];
    }
}
