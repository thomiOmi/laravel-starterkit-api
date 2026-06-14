<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\Role\Actions\ShowRoleAction;
use Modules\Role\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Role
 */
final readonly class ShowController
{
    /**
     * Create a new ShowController instance.
     */
    public function __construct(
        private ShowRoleAction $showRole
    ) {}

    /**
     * Display the specified role.
     *
     * @param  string  $role  The role ID.
     */
    public function __invoke(string $role): JsonDataResponse
    {
        $roleInstance = $this->showRole->handle($role);

        if (! $roleInstance) {
            return new JsonDataResponse(
                data: null,
                status: Response::HTTP_NOT_FOUND,
                message: __('messages.not_found', ['resource' => 'Role'])
            );
        }

        return new JsonDataResponse(
            data: new RoleResource($roleInstance),
            message: __('messages.retrieved', ['resource' => 'Role'])
        );
    }
}
