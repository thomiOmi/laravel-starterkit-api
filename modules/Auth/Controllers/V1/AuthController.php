<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ForgotPasswordAction;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Actions\LogoutDeviceAction;
use Modules\Auth\Actions\LogoutOtherDevicesAction;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Actions\ResendVerificationEmailAction;
use Modules\Auth\Actions\ResetPasswordAction;
use Modules\Auth\Actions\VerifyEmailAction;
use Modules\Auth\DTOs\ForgotPasswordDTO;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\DTOs\ResetPasswordDTO;
use Modules\Auth\DTOs\VerifyEmailDTO;
use Modules\Auth\Requests\ForgotPasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\RegisterRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\Auth\Resources\DeviceResource;
use Modules\User\DTOs\UserDTO;
use Modules\User\Resources\UserResource;

/**
 * @tags Auth
 */
class AuthController extends Controller
{
    /**
     * Login
     *
     * Handle an authentication attempt.
     *
     * @param  LoginRequest  $request  The login request.
     * @param  LoginAction  $action  The login action.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $request->ensureIsNotRateLimited();
        $dto = LoginDTO::fromRequest($request);
        $result = $action->execute($dto, $request);

        /**
         * Success response with user and access token.
         */
        return $this->successResponse(
            [
                'user' => new UserResource($result['user']->load(['roles.permissions', 'permissions'])),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
            __('messages.login_successful')
        );
    }

    /**
     * Register
     *
     * Handle a registration request for the application.
     *
     * @param  RegisterRequest  $request  The user request.
     * @param  RegisterAction  $action  The register action.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request, RegisterAction $action): JsonResponse
    {
        $dto = UserDTO::fromRequest($request);
        $result = $action->execute($dto);

        /**
         * Success response with user and access token.
         */
        return $this->successResponse(
            [
                'user' => new UserResource($result['user']->load(['roles.permissions', 'permissions'])),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
            __('messages.registered_successfully')
        );
    }

    /**
     * Logout
     *
     * Log the user out of the application.
     *
     * @param  Request  $request  The request.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the request
        $request->user()?->currentAccessToken()?->delete();

        return $this->successResponse(null, __('messages.logout_successful'));
    }

    /**
     * Me
     *
     * Get the authenticated user profile.
     *
     * @param  Request  $request  The request.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UserResource($request->user()->load(['roles.permissions', 'permissions'])),
            __('messages.profile_retrieved')
        );
    }

    /**
     * Devices
     *
     * Get the list of active devices/sessions.
     *
     * @param  Request  $request  The request.
     */
    public function devices(Request $request): JsonResponse
    {
        $devices = $request->user()->tokens()->orderBy('last_used_at', 'desc')->get();

        return $this->successResponse(
            DeviceResource::collection($devices),
            __('messages.devices_retrieved')
        );
    }

    /**
     * Logout Device
     *
     * Log out a specific device.
     *
     * @param  Request  $request  The request.
     * @param  LogoutDeviceAction  $action  The logout device action.
     * @param  string  $id  The token ID.
     */
    public function logoutDevice(Request $request, LogoutDeviceAction $action, string $id): JsonResponse
    {
        $action->execute($request, $id);

        return $this->successResponse(null, __('messages.device_logged_out'));
    }

    /**
     * Logout Other Devices
     *
     * Log out all other devices except the current one.
     *
     * @param  Request  $request  The request.
     * @param  LogoutOtherDevicesAction  $action  The logout other devices action.
     */
    public function logoutOtherDevices(Request $request, LogoutOtherDevicesAction $action): JsonResponse
    {
        $action->execute($request);

        return $this->successResponse(null, __('messages.other_devices_logged_out'));
    }

    /**
     * Forgot Password
     *
     * Send a reset link to the given user.
     *
     * @param  ForgotPasswordRequest  $request  The user request.
     * @param  ForgotPasswordAction  $action  The forgot password action.
     *
     * @unauthenticated
     */
    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): JsonResponse
    {
        $dto = ForgotPasswordDTO::fromRequest($request);
        $message = $action->execute($dto);

        return $this->successResponse(null, $message);
    }

    /**
     * Reset Password
     *
     * Reset the given user's password.
     *
     * @param  ResetPasswordRequest  $request  The user request.
     * @param  ResetPasswordAction  $action  The reset password action.
     *
     * @unauthenticated
     */
    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $dto = ResetPasswordDTO::fromRequest($request);
        $message = $action->execute($dto);

        return $this->successResponse(null, $message);
    }

    /**
     * Verify Email
     *
     * Mark the user's email address as verified.
     *
     * @param  Request  $request  The request.
     * @param  VerifyEmailAction  $action  The verify email action.
     */
    public function verifyEmail(Request $request, VerifyEmailAction $action): JsonResponse
    {
        $dto = VerifyEmailDTO::fromRequest($request);
        $message = $action->execute($dto);

        return $this->successResponse(null, $message);
    }

    /**
     * Resend Email Verification
     *
     * Resend the email verification notification.
     *
     * @param  Request  $request  The request.
     * @param  ResendVerificationEmailAction  $action  The resend verification email action.
     */
    public function resendVerificationEmail(Request $request, ResendVerificationEmailAction $action): JsonResponse
    {
        $message = $action->execute($request->user());

        return $this->successResponse(null, $message);
    }
}
