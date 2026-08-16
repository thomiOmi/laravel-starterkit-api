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
        $context = $this->resolveBulkContext($routeName);

        if ($context === null) {
            return false;
        }

        [$resource, $action] = $context;

        if ($this->has('action') && $this->string('action')->toString() !== $action) {
            return false;
        }

        if ($resource === 'user') {
            if ($action === 'delete') {
                return $user->can(PermissionEnum::UserDelete->value);
            }

            if ($action === 'restore') {
                return $user->can(PermissionEnum::UserRestore->value);
            }

            return false;
        }

        if ($resource === 'role') {
            if ($action === 'delete') {
                return $user->can(PermissionEnum::RoleDelete->value);
            }

            return false;
        }

        return false;
    }

    /**
     * Resolve the resource and action segments from the route name.
     *
     * Bulk routes follow `v1.{module}.{resource}.bulk.{action}`, so the
     * resource is the segment before `bulk` and the action the one after.
     *
     * @return array{0: string, 1: string}|null
     */
    private function resolveBulkContext(string $routeName): ?array
    {
        $segments = explode('.', $routeName);

        $index = array_search('bulk', $segments, true);

        if ($index === false) {
            return null;
        }

        $resource = $segments[$index - 1] ?? null;
        $action = $segments[$index + 1] ?? null;

        if ($resource === null || $action === null) {
            return null;
        }

        return [$resource, $action];
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
