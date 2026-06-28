<?php

declare(strict_types=1);

namespace Modules\User\Requests\V1;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Models\User;

final class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $this->user();

        return (bool) $currentUser?->can('user.edit');
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
