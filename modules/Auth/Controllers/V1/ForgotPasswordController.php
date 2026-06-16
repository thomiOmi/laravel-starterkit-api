<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

#[Group('Auth')]
final readonly class ForgotPasswordController
{
    #[Endpoint(operationId: 'forgotPassword', title: 'Forgot Password')]
    #[Response(status: 200, description: 'Password reset link sent', examples: ['status' => 200, 'message' => 'Password reset link sent.', 'data' => null])]
    public function __invoke(Request $request): JsonDataResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink(
            $request->only('email'),
        );

        if ($status === Password::RESET_LINK_SENT) {
            return new JsonDataResponse(
                data: null,
                message: __($status),
            );
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
