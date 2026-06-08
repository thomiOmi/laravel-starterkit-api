<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\DataResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Modules\User\Models\User;

/**
 * @tags Auth
 */
final readonly class VerifyEmailController
{
    /**
     * Verify the user's email address using a signed URL.
     */
    public function __invoke(Request $request): DataResponse
    {
        /** @var User $user */
        $user = User::findOrFail($request->route('id'));

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            abort(403, __('auth.invalid_verification_link'));
        }

        if ($user->hasVerifiedEmail()) {
            return new DataResponse(
                message: __('auth.verified')
            );
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return new DataResponse(
            message: __('auth.verified')
        );
    }
}
