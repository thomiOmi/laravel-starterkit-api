<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\DeleteDeviceAction;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Auth')]
/**
 * @authenticated
 */
final readonly class DeleteDeviceController
{
    public function __construct(
        private DeleteDeviceAction $deleteDevice
    ) {}

    #[Endpoint(operationId: 'deleteDevice', title: 'Delete Device')]
    #[Response(
        status: 204,
        description: 'Device revoked successfully. The specified access token is deleted. No content is returned.',
    )]
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
    #[Response(
        status: 404,
        description: 'Device (token) not found for the authenticated user.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Not Found',
            'status' => 404,
            'detail' => 'The requested resource does not exist.',
        ]],
    )]
    public function __invoke(Request $request, string $device): JsonResponse
    {

        $this->deleteDevice->handle($request->user(), $device);

        return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
    }
}
