<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Requests\V1;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Rules\Unique;
use Modules\Media\Rules\AllowedFileName;

/**
 * @phpstan-type UpdateProfileRules array<string, array<int, ValidationRule|Unique|Dimensions|string>>
 */
class UpdateProfileRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalize the email before validation so the unique rule compares
     * case-insensitively across database drivers.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->normalizeEmail();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return UpdateProfileRules
     */
    public function rules(): array
    {
        $model = config()->string('auth.providers.users.model');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', $this->uniqueEmailRule($model)],
            'avatar' => [
                'sometimes',
                'nullable',
                'file',
                'image',
                'max:'.config()->integer('media.max_size'),
                Rule::dimensions()->minWidth(32)->minHeight(32)->ratio(1 / 1),
                new AllowedFileName,
            ],
        ];
    }

    private function uniqueEmailRule(string $model): Unique
    {
        $userId = $this->user()?->getAuthIdentifier();

        return is_string($userId)
            ? Rule::unique($model)->ignore($userId)
            : Rule::unique($model);
    }
}
