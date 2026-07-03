<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Actions\ListDevicesAction;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\DeviceResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class ListDevicesController
{
    public function __construct(
        private ListDevicesAction $listDevices
    ) {}

    /**
     * @return SuccessResponse<AnonymousResourceCollection>|ProblemResponse
     */
    public function __invoke(Request $request): SuccessResponse|ProblemResponse
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

        $devices = $this->listDevices->handle($currentUser);

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Devices']),
            DeviceResource::collection($devices),
        );
    }
}
