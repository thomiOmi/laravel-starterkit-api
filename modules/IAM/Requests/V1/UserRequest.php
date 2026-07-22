<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Contracts\Identity;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
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
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        /** @var Identity $user */
        if ($this->isMethod('POST')) {
            return $user->can(PermissionEnum::UserCreate->value);
        }

        /** @var Identity|Model|null $targetUser */
        $targetUser = $this->route('user');

        if (! $targetUser instanceof Identity) {
            return $user->can(PermissionEnum::UserEdit->value);
        }

        $canEdit = $targetUser->getAuthIdentifier() === $user->getAuthIdentifier() || $user->can(PermissionEnum::UserEdit->value);

        if (! $canEdit) {
            return false;
        }

        if ($user->hasRole(RoleEnum::SuperAdmin->value)) {
            return true;
        }

        if ($targetUser->hasRole(RoleEnum::SuperAdmin->value)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, Password|Unique|ValidationRule|string>> The validation rules.
     */
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $userId = $routeUser instanceof Model ? $routeUser->getKey() : (is_string($routeUser) ? $routeUser : null);
        $userId = is_string($userId) || is_int($userId) ? (string) $userId : null;

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
