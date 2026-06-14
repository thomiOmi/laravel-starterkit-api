<?php

declare(strict_types=1);

namespace Modules\User\Controllers\Auth;

use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * @tags Auth
 */
final readonly class ForgotPasswordController
{
    public function __invoke(Request $request): JsonResponse
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
