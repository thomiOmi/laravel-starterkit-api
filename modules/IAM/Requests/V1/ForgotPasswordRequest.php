<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

final class ForgotPasswordRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Unique>>
     */
    public function rules(): array
    {
        return [
            'email' => $this->emailRules(unique: false),
        ];
    }
}
