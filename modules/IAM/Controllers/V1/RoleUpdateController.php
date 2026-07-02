<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\IAM\Actions\UpdateRoleAction;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\RoleRequest;
use Modules\IAM\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class RoleUpdateController
{
    public function __construct(
        private UpdateRoleAction $updateRole,
    ) {}

    /**
     * Update the specified role in storage.
     *
     * @param  RoleRequest  $request  The validated role update request.
     * @param  string  $id  The role ID.
     */
    /**
     * @response SuccessResponse<RoleResource>
     */
    public function __invoke(RoleRequest $request, string $id): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: Response::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        $role = $this->updateRole->handle($id, $request->payload());

        if (! $role) {
            return new ProblemResponse(
                title: 'Not Found',
                status: Response::HTTP_NOT_FOUND,
                detail: __('general.not_found', ['resource' => 'Role']),
            );
        }

        return new SuccessResponse(
            'OK',
            __('general.updated', ['resource' => 'Role']),
            new RoleResource($role),
        );
    }
}
