<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\RegisterAction;
use Modules\IAM\Requests\V1\RegisterRequest;
use Modules\IAM\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class RegisterController
{
    public function __construct(
        private RegisterAction $registerAction
    ) {}

    /**
     * @return SuccessResponse<array{user: UserResource, access_token: string, token_type: string}>
     */
    public function __invoke(RegisterRequest $request): SuccessResponse
    {
        $result = $this->registerAction->handle(
            $request->payload(),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return new SuccessResponse(
            data: [
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
            title: 'Created',
            detail: __('auth.registered'),
            status: Response::HTTP_CREATED,
        );
    }
}
