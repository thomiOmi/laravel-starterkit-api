<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\IAM\Actions\RegisterAction;
use Modules\IAM\Http\Requests\V1\RegisterRequest;
use Modules\IAM\Http\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class RegisterController extends Controller
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
        $result['user']->load(['roles:id,name,guard_name', 'permissions:id,name']);

        return new SuccessResponse(
            data: [
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
            title: 'Created',
            detail: __('auth.register_success'),
            status: Response::HTTP_CREATED,
        );
    }
}
