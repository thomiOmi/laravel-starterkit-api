<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\DeleteRoleAction;
use Modules\Role\Models\Role;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Role
 */
final readonly class DestroyController
{
    public function __construct(
        private DeleteRoleAction $deleteRole,
    ) {}

    /**
     * Remove the specified role from storage.
     */
    public function __invoke(Role $role): JsonDataResponse
    {
        $this->deleteRole->handle($role);

        return new JsonDataResponse(
            data: null,
            status: Response::HTTP_NO_CONTENT,
            message: __('messages.deleted', ['resource' => 'Role'])
        );
    }
}
