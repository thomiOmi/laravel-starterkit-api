<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Modules\IAM\Payloads\V1\UserPayload;

/**
 * User Request
 *
 * The request parameters for creating or updating a user.
 */
final class UserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $authenticatedUser = $this->user();

        if ($authenticatedUser === null) {
            return false;
        }

        if ($this->isMethod('POST')) {
            return $authenticatedUser->can('user.create');
        }

        $userId = $this->route('user');
        $id = $authenticatedUser->getKey();

        // Check if the user is updating their own profile or has permission
        return (is_string($id) || is_int($id) ? (string) $id : '') === $userId || $authenticatedUser->can('user.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, Password|Unique|ValidationRule|string>> The validation rules.
     */
    public function rules(): array
    {
        $userId = $this->route('user');
        $userId = is_string($userId) ? $userId : null;

        return [
            ...$this->profileRules($userId),
            'password' => $this->passwordRules($this->isMethod('POST')),
        ];
    }

    /**
     * Get the payload for the request.
     */
    public function payload(): UserPayload
    {
        return UserPayload::fromRequest($this);
    }
}
