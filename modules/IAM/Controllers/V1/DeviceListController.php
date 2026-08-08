<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Models\User;
use Modules\IAM\Requests\V1\DeviceListRequest;
use Modules\IAM\Resources\DeviceResource;

final readonly class DeviceListController extends Controller
{
    /**
     * List all authenticated user devices.
     *
     * @return SuccessResponse<AnonymousResourceCollection>
     */
    public function __invoke(DeviceListRequest $request, #[CurrentUser] User $currentUser): SuccessResponse
    {
        $devices = PersonalAccessToken::query()
            ->where('tokenable_id', $currentUser->getKey())
            ->where('tokenable_type', $currentUser->getMorphClass())
            ->select([
                'id',
                'name',
                'last_used_at',
                'created_at',
                'ip_address',
                'user_agent',
            ])
            ->allowedSearch()
            ->allowedFilters()
            ->allowedSorts()
            ->allowedFields()
            ->allowedIncludes()
            ->paginate(
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
