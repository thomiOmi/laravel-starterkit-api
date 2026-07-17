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
     * @var Identity|null The cached target user instance to prevent duplicate lookups.
     */
    private ?Identity $targetUserInstance = null;

    /**
     * Retrieve the target user instance based on the request route, memoized for performance.
     *
     * @return Identity|null The target user model or null if not applicable.
     */
    public function getTargetUser(): ?Identity
    {
        if ($this->targetUserInstance !== null) {
            return $this->targetUserInstance;
        }

        $userId = $this->route('user');

        if (! is_string($userId)) {
            return null;
        }

        /** @var class-string<Model> $model */
        $model = (string) config('auth.providers.users.model', 'Modules\IAM\Models\User');

        /** @var Identity $targetUser */
        $targetUser = $model::query()->findOrFail($userId);

        $this->targetUserInstance = $targetUser;

        return $targetUser;
    }

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

        /** @var string|int $id */
        $id = $user->getAuthIdentifier();

        $canEdit = (string) $id === $userId || $user->can(PermissionEnum::UserEdit->value);

        if (! $canEdit) {
            return false;
        }

        // If the actor is already a SuperAdmin, they can edit anything.
        if ($user->hasRole(RoleEnum::SuperAdmin->value)) {
            return true;
        }

        // If the actor is NOT a SuperAdmin, they cannot edit a SuperAdmin.
        $targetUser = $this->getTargetUser();

        if ($targetUser !== null && $targetUser->hasRole(RoleEnum::SuperAdmin->value)) {
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
