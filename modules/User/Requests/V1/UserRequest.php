<?php

declare(strict_types=1);

namespace Modules\User\Requests\V1;

use App\Traits\Rules\PasswordValidationRules;
use App\Traits\Rules\ProfileValidationRules;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
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
        /** @var (Authenticatable&User)|null $authenticatedUser */
        $authenticatedUser = $this->user();

        if ($authenticatedUser === null) {
            return false;
        }

        if ($this->isMethod('POST')) {
            return $authenticatedUser->can('user.create');
        }

        $userId = $this->getUserId();

        // Check if the user is updating their own profile or has permission
        return (string) $authenticatedUser->getKey() === $userId || $authenticatedUser->can('user.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, Password|Unique|ValidationRule|string>> The validation rules.
     */
    public function rules(): array
    {
        $userId = $this->getUserId();

        return [
            ...$this->profileRules($userId),
            'password' => $this->passwordRules($this->isMethod('POST')),
        ];
    }

    /**
     * Get the user ID from the route.
     */
    public function getUserId(): string
    {
        $userId = $this->route('user');

        return $userId instanceof Model
            ? (string) $userId->getKey()
            : (string) $userId;
    }

    /**
     * Get the payload for the request.
     */
    public function payload(): UserPayload
    {
        return UserPayload::fromRequest($this);
    }
}
