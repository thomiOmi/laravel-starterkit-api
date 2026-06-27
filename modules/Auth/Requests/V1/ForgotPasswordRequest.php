<?php

declare(strict_types=1);

namespace Modules\Auth\Requests\V1;

use App\Traits\Rules\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

final class ForgotPasswordRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => $this->emailRules(unique: false),
        ];
    }
}
