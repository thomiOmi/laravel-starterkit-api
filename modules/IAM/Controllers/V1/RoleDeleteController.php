<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Request;
use Modules\IAM\Actions\DeleteRoleAction;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class RoleDeleteController
{
    public function __construct(
        private DeleteRoleAction $deleteRole,
    ) {}

    /**
     * Remove the specified role from storage.
     *
     * @param  string  $role  The role ID.
     * @return SuccessResponse<null>|ProblemResponse
     */
    public function __invoke(Request $request, string $role): SuccessResponse|ProblemResponse
    {
        if ($this->deleteRole->handle($role)) {
            return new SuccessResponse(null, status: SymfonyResponse::HTTP_NO_CONTENT);
        }

        return new ProblemResponse(
            title: __('auth.http_forbidden'),
            status: SymfonyResponse::HTTP_FORBIDDEN,
            detail: __('general.resource_delete_error', ['resource' => 'Role']),
        );
    }
}
