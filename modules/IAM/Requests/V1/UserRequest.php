<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Contracts\Identity;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
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
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        /** @var Identity $user */
        if ($this->isMethod('POST')) {
            return $user->can(PermissionEnum::UserCreate->value);
        }

        $userId = $this->route('user');

        if (! is_string($userId)) {
            return $user->can(PermissionEnum::UserEdit->value);
        }

        $id = $user->getAuthIdentifier();

        $canEdit = (string) (is_string($id) || is_int($id) ? $id : '') === $userId || $user->can(PermissionEnum::UserEdit->value);

        if (! $canEdit) {
            return false;
        }

        // If the actor is already a SuperAdmin, they can edit anything.
        if ($user->hasRole(RoleEnum::SuperAdmin->value)) {
            return true;
        }

        // If the actor is NOT a SuperAdmin, they cannot edit a SuperAdmin.
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
        $model = (string) config('auth.providers.users.model', 'Modules\IAM\Models\User');
        $targetUser = $model::query()->find($userId);

        if ($targetUser instanceof Identity && $targetUser->hasRole(RoleEnum::SuperAdmin->value)) {
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
