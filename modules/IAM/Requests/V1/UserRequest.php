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

        $userId = $this->route('user');

        if (! is_string($userId) && ! $userId instanceof Identity) {
            return $user->can(PermissionEnum::UserEdit->value);
        }

        /** @var string|int $id */
        $id = $user->getAuthIdentifier();

        if ($userId instanceof Identity) {
            $authId = $userId->getAuthIdentifier();
            $userIdString = is_string($authId) || is_int($authId) ? (string) $authId : '';
        } else {
            $userIdString = $userId;
        }
        $canEdit = (string) $id === $userIdString || $user->can(PermissionEnum::UserEdit->value);

        if (! $canEdit) {
            return false;
        }

        // If the actor is already a SuperAdmin, they can edit anything.
        if ($user->hasRole(RoleEnum::SuperAdmin->value)) {
            return true;
        }

        // If the actor is NOT a SuperAdmin, they cannot edit a SuperAdmin.
        if ($userId instanceof Identity) {
            $targetUser = $userId;
        } else {
            /** @var class-string<Model> $model */
            $model = (string) config('auth.providers.users.model', 'Modules\IAM\Models\User');
            /** @var Identity $targetUser */
            $targetUser = $model::query()->findOrFail($userId);
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
        $userId = $this->route('user');
        if ($userId instanceof Identity) {
            $authId = $userId->getAuthIdentifier();
            $userIdString = is_string($authId) || is_int($authId) ? (string) $authId : '';
        } else {
            $userIdString = is_string($userId) ? $userId : null;
        }

        return [
            ...$this->profileRules($userIdString),
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
