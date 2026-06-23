<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Role\Actions\DeleteRoleAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class DeleteController
{
    public function __construct(
        private DeleteRoleAction $deleteRole,
    ) {}

    /**
     * Remove the specified role from storage.
     *
     * @param  string  $role  The role ID.
     */
    public function __invoke(Request $request, string $role): JsonResponse|ProblemResponse
    {
        /** @var Authenticatable&User $user */
        $user = $request->user();

        if (! $user->can('role.delete')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: SymfonyResponse::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        if ($this->deleteRole->handle($role)) {
            return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
        }

        return new ProblemResponse(
            title: 'Forbidden',
            status: SymfonyResponse::HTTP_FORBIDDEN,
            detail: __('general.delete_error', ['resource' => 'Role']),
        );
    }
}
