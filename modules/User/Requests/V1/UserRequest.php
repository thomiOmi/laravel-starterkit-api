<?php

declare(strict_types=1);

namespace Modules\User\Requests\V1;

use App\Traits\Rules\PasswordValidationRules;
use App\Traits\Rules\ProfileValidationRules;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Modules\User\Models\User;
use Modules\User\Payloads\V1\UserPayload;

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
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $this->user();

        if ($currentUser === null) {
            return false;
        }

        if ($this->isMethod('POST')) {
            return $currentUser->can('user.create');
        }

        $userId = $this->getUserId();

        // Check if the user is updating their own profile or has permission
        return (string) $currentUser->getKey() === $userId || $currentUser->can('user.edit');
    }

    /**
     * Get the user ID from the route.
     */
    public function getUserId(): string
    {
        $userId = $this->route('user');

        $id = is_string($userId) || is_int($userId) ? (string) $userId : '';

        return $id;
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
