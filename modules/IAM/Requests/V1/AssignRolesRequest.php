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

        if ($userId instanceof Model) {
            /** @var Identity&Model $targetUser */
            $targetUser = $userId;
        } elseif (is_string($userId)) {
            /** @var class-string<Model> $model */
            $model = (string) config('auth.providers.users.model', 'Modules\IAM\Models\User');
            /** @var Identity&Model $targetUser */
            $targetUser = $model::query()->findOrFail($userId);
        } else {
            return true;
        }

        if ($targetUser->hasRole(RoleEnum::SuperAdmin->value)) {
            return false;
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
