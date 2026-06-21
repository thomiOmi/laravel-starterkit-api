<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Bulk Action Request
 *
 * Validate bulk action requests for any module.
 */
#[BodyParameter(name: 'action', description: 'The bulk action to perform.', required: true, example: 'delete')]
#[BodyParameter(name: 'ids', description: 'An array of resource IDs (ULID) to perform the action on.', required: true, example: ['01hpv4n8f8xrd2m8q0e4x8j9v1', '01hpv4n8f8xrd2m8q0e4x8j9v2'])]
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
        $segments = explode('.', $routeName);
        $action = $this->resolveBulkAction($routeName);

        if ($action === null) {
            return false;
        }

        $module = $segments[2] ?? '';

        if ($module === 'user') {
            return $action === 'delete' ? $user->can('user.delete') : ($action === 'restore' ? $user->can('user.edit') : false);
        }

        if ($module === 'role') {
            return $action === 'delete' ? $user->can('role.delete') : ($action === 'restore' ? $user->can('role.edit') : false);
        }

        if ($module === 'permission') {
            return $action === 'delete' ? $user->can('permission.delete') : ($action === 'restore' ? $user->can('permission.edit') : false);
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
