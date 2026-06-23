<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Role\Actions\ShowPermissionAction;
use Modules\Role\Resources\PermissionResource;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class PermissionShowController
{
    public function __construct(
        private ShowPermissionAction $showPermission
    ) {}

    /**
     * Display the specified permission.
     */
    public function __invoke(Request $request, string $permission): SuccessResponse|ProblemResponse
    {
        /** @var Authenticatable&User $user */
        $user = $request->user();

        if (! $user->can('permission.view')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        $permission = $this->showPermission->handle($permission);

        if ($permission === null) {
            return new ProblemResponse(
                title: 'Not Found',
                status: Response::HTTP_NOT_FOUND,
                detail: __('general.not_found', ['resource' => 'Permission']),
            );
        }

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Permission']),
            new PermissionResource($permission),
        );
    }
}
