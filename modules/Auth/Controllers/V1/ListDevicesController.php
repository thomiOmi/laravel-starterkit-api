<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ListDevicesAction;
use Modules\Auth\Resources\DeviceResource;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
    #[Response(
        status: 200,
        description: 'List of authenticated devices (personal access tokens) for the current user. Each device includes a boolean `is_current` flag to identify the device used for this request.',
        examples: [[
            'status' => 200,
            'message' => 'Devices retrieved.',
            'data' => [
                ['id' => 1, 'name' => 'test-device', 'last_used_at' => '2026-06-16 20:00:00', 'is_current' => true],
                ['id' => 2, 'name' => 'second-device', 'last_used_at' => '2026-06-15 10:00:00', 'is_current' => false],
            ],
        ]],
    )]
    #[Response(
        status: 401,
        description: 'Authentication required. The request lacks a valid Bearer token.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Unauthenticated',
            'status' => 401,
            'message' => 'Unauthenticated',
            'detail' => 'You must be authenticated to access this resource.',
        ]],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $devices = $this->listDevices->handle($user);

        $resource = DeviceResource::collection($devices);
        /** @var array<string, mixed> $raw */
        $raw = $resource->toResponse($request)->getData(true);

        return new JsonResponse(
            array_filter([
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('general.retrieved', ['resource' => 'Devices']),
                'data' => $raw['data'] ?? [],
                'meta' => $raw['meta'] ?? null,
                'links' => $raw['links'] ?? null,
            ], fn ($value) => $value !== null),
            SymfonyResponse::HTTP_OK,
        );
    }
}
