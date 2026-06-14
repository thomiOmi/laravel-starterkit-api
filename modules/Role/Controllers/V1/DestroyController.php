<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\DeleteRoleAction;
use Modules\Role\Models\Role;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Role Management
 *
 * @authenticated
 */
final readonly class DestroyController
{
    public function __construct(
        private DeleteRoleAction $deleteRole,
    ) {}

    /**
     * Remove the specified role from storage.
     *
     * @param  Role  $role  The role model instance.
     */
    public function __invoke(Role $role): JsonDataResponse|Response
    {
        if ($this->deleteRole->handle($role)) {
            return response()->noContent();
        }

        return new JsonDataResponse(
            data: null,
            status: Response::HTTP_FORBIDDEN,
            message: __('messages.delete_error', ['resource' => 'Role'])
        );
    }
}
