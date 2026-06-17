<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\LogoutOtherDevicesAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Auth')]
/**
 * @authenticated
 */
final readonly class LogoutOtherDevicesController
{
    public function __construct(
        private LogoutOtherDevicesAction $logoutOtherDevices
    ) {}

    #[Endpoint(operationId: 'logoutOtherDevices', title: 'Logout Other Devices')]
    #[Response(
        status: 204,
        description: 'All other sessions revoked. Only the current device remains authenticated. No content is returned.',
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
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->logoutOtherDevices->handle($user);

        return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
    }
}
