<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Actions\ListDevicesAction;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\DeviceListRequest;
use Modules\IAM\Resources\DeviceResource;

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
    public function __invoke(DeviceListRequest $request): SuccessResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        $devices = $this->listDevices->handle($currentUser);

        return new SuccessResponse(
            data: DeviceResource::collection($devices),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Devices']),
        );
    }
}
