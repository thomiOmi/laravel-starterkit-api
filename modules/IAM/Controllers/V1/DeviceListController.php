<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Actions\ListDevicesAction;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\DeviceResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeviceListController
{
    public function __construct(
        private ListDevicesAction $listDevices
    ) {}

    /**
     * List all authenticated user devices.
     *
     * @return SuccessResponse<AnonymousResourceCollection>
     */
    public function __invoke(Request $request): SuccessResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        $devices = $this->listDevices->handle(
            $currentUser,
            $request->integer('page.size', 10),
            $request->integer('page.number', 1)
        );

        return new SuccessResponse(
            data: DeviceResource::collection($devices),
            title: 'OK',
            detail: __('general.retrieved', ['resource' => 'Devices']),
            status: Response::HTTP_OK,
        );
    }
}
