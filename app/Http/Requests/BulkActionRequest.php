<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

final class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $routeName = (string) $this->route()?->getName();
        $action = $this->resolveBulkAction($routeName);

        if ($action === null) {
            return false;
        }

        if (str_contains($routeName, '.user.')) {
            if ($action === 'delete') {
                return $user->can(PermissionEnum::UserDelete->value);
            }

            if ($action === 'restore') {
                return $user->can(PermissionEnum::UserRestore->value);
            }

            return false;
        }

        if (str_contains($routeName, '.role.')) {
            if ($action === 'delete') {
                return $user->can(PermissionEnum::RoleDelete->value);
            }

            if ($action === 'restore') {
                return $user->can(PermissionEnum::RoleEdit->value);
            }

            return false;
        }

        return false;
    }

    private function resolveBulkAction(string $routeName): ?string
    {
        $segments = explode('.', $routeName);

        $routeAction = null;
        $index = array_search('bulk', $segments, true);

        if ($index !== false) {
            $routeAction = $segments[$index + 1] ?? null;
        }

        if ($routeAction !== null && $this->has('action')) {
            $bodyAction = $this->string('action')->toString();

            if ($bodyAction !== $routeAction) {
                return null;
            }
        }

        $bodyAction = $this->string('action')->toString();

        return $routeAction ?? ($bodyAction !== '' ? $bodyAction : null);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['required', 'string', 'ulid'],
            'action' => ['sometimes', 'string', 'in:delete,restore'],
        ];
    }
}
