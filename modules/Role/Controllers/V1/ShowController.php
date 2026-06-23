<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Role\Actions\ShowRoleAction;
use Modules\Role\Resources\RoleResource;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class ShowController
{
    public function __construct(
        private ShowRoleAction $showRole
    ) {}

    /**
     * Display the specified role.
     */
    public function __invoke(Request $request, string $role): SuccessResponse|ProblemResponse
    {
        /** @var Authenticatable&User $user */
        $user = $request->user();

        if (! $user->can('role.view')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        $role = $this->showRole->handle($role);

        if ($role === null) {
            return new ProblemResponse(
                title: 'Not Found',
                status: Response::HTTP_NOT_FOUND,
                detail: __('general.not_found', ['resource' => 'Role']),
            );
        }

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Role']),
            new RoleResource($role),
        );
    }
}
