<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ListDevicesAction;
use Modules\Auth\Resources\DeviceResource;
use Modules\User\Models\User;

#[Group('Auth')]
/**
 * @authenticated
 */
final readonly class ListDevicesController
{
    public function __construct(
        private ListDevicesAction $listDevices
    ) {}

    #[Endpoint(operationId: 'listDevices', title: 'List Devices')]
    #[Response(status: 200, description: 'List of authenticated devices retrieved successfully.', type: 'SuccessResponse<array<DeviceResource>>')]
    #[Response(
        status: 401,
        description: 'Authentication required. The request lacks a valid Bearer token.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Unauthenticated',
            'status' => 401,
            'detail' => 'You must be authenticated to access this resource.',
        ]],
    )]
    public function __invoke(Request $request): SuccessResponse
    {
        /** @var User $user */
        $user = $request->user();

        $devices = $this->listDevices->handle($user);

        $resource = DeviceResource::collection($devices);
        /** @var array<string, mixed> $raw */
        $raw = $resource->toResponse($request)->getData(true);

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Devices']),
            $raw['data'] ?? [],
            200,
            array_filter([
                'meta' => $raw['meta'] ?? null,
                'links' => $raw['links'] ?? null,
            ], fn ($value) => $value !== null),
        );
    }
}
