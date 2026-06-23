<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Bulk Action Request
 *
 * Validate bulk action requests for any module.
 */
class BulkActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
                return $user->can('user.delete');
            }

            if ($action === 'restore') {
                return $user->can('user.edit');
            }

            return false;
        }

        if (str_contains($routeName, '.role.')) {
            if ($action === 'delete') {
                return $user->can('role.delete');
            }

            if ($action === 'restore') {
                return $user->can('role.edit');
            }

            return false;
        }

        if (str_contains($routeName, '.permissions.')) {
            if ($action === 'delete') {
                return $user->can('permission.delete');
            }

            if ($action === 'restore') {
                return $user->can('permission.edit');
            }

            return false;
        }

        return false;
    }

    /**
     * Resolve the bulk action from the route name or request body.
     */
    private function resolveBulkAction(string $routeName): ?string
    {
        $segments = explode('.', $routeName);

        $routeAction = null;
        if (in_array('bulk', $segments, true)) {
            $index = array_search('bulk', $segments, true);
            $routeAction = $segments[$index + 1] ?? null;
        }

        if ($routeAction !== null && $this->has('action')) {
            $bodyAction = $this->string('action')->toString();

            if ($bodyAction !== $routeAction) {
                return null;
            }
        }

        return $routeAction ?? ($this->string('action')->toString() ?: null);
    }

    /**
     * Get the validation rules that apply to the request.
     *
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
