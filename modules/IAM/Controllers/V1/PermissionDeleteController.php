<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\IAM\Actions\DeletePermissionAction;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class PermissionDeleteController
{
    public function __construct(
        private DeletePermissionAction $deletePermission
    ) {}

    /**
     * Remove the specified permission.
     *
     * @param  string  $id  The permission ID.
     *
     * @throws AuthenticationException Full authentication is required to access permission management.
     * @throws AuthorizationException You do not have permission to delete permissions.
     * @throws ModelNotFoundException The specified permission was not found.
     */
    public function __invoke(Request $request, string $id): JsonResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: SymfonyResponse::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        if (! $currentUser->can('permission.delete')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: SymfonyResponse::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        if ($this->deletePermission->handle($id)) {
            return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
        }

        return new ProblemResponse(
            title: 'Forbidden',
            status: SymfonyResponse::HTTP_FORBIDDEN,
            detail: __('general.delete_error', ['resource' => 'Permission']),
        );
    }
}
