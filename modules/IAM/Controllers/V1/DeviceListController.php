<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Actions\ListDevicesAction;
use Modules\IAM\Filters\DeviceFilter;
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
    public function __invoke(DeviceListRequest $request, #[CurrentUser] User $currentUser): SuccessResponse
    {
        $devices = $this->listDevices->handle(
            $currentUser,
            filter: new DeviceFilter($request),
            perPage: $request->getPerPage(),
            page: $request->getPage(),
        );

        return new SuccessResponse(
            data: DeviceResource::collection($devices),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Devices']),
        );
    }
}
