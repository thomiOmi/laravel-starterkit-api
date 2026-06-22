<?php

declare(strict_types=1);

namespace Modules\User\Requests\V1;

use App\Traits\Rules\PasswordValidationRules;
use App\Traits\Rules\ProfileValidationRules;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Modules\User\Payloads\V1\UserPayload;

/**
 * User Request
 *
 * The request parameters for creating or updating a user.
 */
#[BodyParameter(name: 'name', description: 'The full name of the user.', required: true, example: 'John Doe')]
#[BodyParameter(name: 'email', description: 'The email address of the user. Must be unique.', required: true, example: 'john@example.com')]
#[BodyParameter(name: 'password', description: 'The password (min 8 characters). Required on create, optional on update.', example: 'password123')]
#[BodyParameter(name: 'password_confirmation', description: 'Password confirmation, must match password.', example: 'password123')]
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

        // Check if the user is updating their own profile or has permission
        return $authenticatedUser->id === $userId || $authenticatedUser->can('user.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, Password|Unique|ValidationRule|string>> The validation rules.
     */
    public function rules(): array
    {
        $userId = $this->route('user');

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
