<?php

declare(strict_types=1);

namespace Modules\User\Requests\V1;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Modules\User\Models\User;
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
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($this->isMethod('POST')) {
            return $user->can('user.create');
        }

        // For updates, allow if user has permission OR is updating their own profile
        $userId = $this->getUserId();

        return $user->can('user.edit') || $user->id === $userId;
    }

    /**
     * Get the user ID from the route.
     */
    private function getUserId(): string
    {
        $user = $this->route('user');

        return $user instanceof User ? $user->id : (string) $user;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Unique|ValidationRule>> The validation rules.
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                $this->isMethod('POST') ? 'required' : 'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
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
