<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\DataResponse;
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
    public function __invoke(Role $role): DataResponse
    {
        $this->deleteRole->handle($role);

        return new DataResponse(
            data: null,
            status: Response::HTTP_OK,
            message: __('messages.deleted', ['resource' => 'Role'])
        );
    }
}
