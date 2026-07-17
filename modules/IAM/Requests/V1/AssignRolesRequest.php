<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Contracts\Identity;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignRolesRequest extends FormRequest
{
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

    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        /** @var Identity $user */
        $canEdit = $user->can(PermissionEnum::UserEdit->value);

        if (! $canEdit) {
            return false;
        }

        // If the actor is already a SuperAdmin, they can edit anything.
        if ($user->hasRole(RoleEnum::SuperAdmin->value)) {
            return true;
        }

        /** @var array<int, string> $roles */
        $roles = $this->input('roles', []);

        if (in_array(RoleEnum::SuperAdmin->value, $roles, true)) {
            return false;
        }

        $userId = $this->route('user');

        if (is_string($userId)) {
            $targetUser = $this->getTargetUser();

            if ($targetUser !== null && $targetUser->hasRole(RoleEnum::SuperAdmin->value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::exists('roles', 'name')],
        ];
    }
}
