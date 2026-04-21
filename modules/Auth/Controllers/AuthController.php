<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ForgotPasswordAction;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Actions\ResetPasswordAction;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;

class AuthController extends Controller
{
    /**
     * Handle a registration request for the application.
     *
     * @return JsonResponse
     */
    public function register(Request $request, RegisterAction $action)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $result = $action->execute($request->all());

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'access_token' => $result['access_token'],
            'token_type' => $result['token_type'],
        ], 'User registered successfully. Please verify your email.');
    }

    /**
     * Handle an authentication attempt.
     *
     * @return JsonResponse
     */
    public function login(Request $request, LoginAction $action)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $result = $action->execute($request->only('email', 'password'));

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'access_token' => $result['access_token'],
            'token_type' => $result['token_type'],
        ], 'Login successful');
    }

    /**
     * Log the user out of the application.
     *
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the request
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout successful');
    }

    /**
     * Get the authenticated user profile.
     *
     * @return JsonResponse
     */
    public function me(Request $request)
    {
        return $this->successResponse(new UserResource($request->user()), 'User profile retrieved successfully');
    }

    /**
     * Send a reset link to the given user.
     *
     * @return JsonResponse
     */
    public function forgotPassword(Request $request, ForgotPasswordAction $action)
    {
        $request->validate(['email' => 'required|email']);

        $message = $action->execute($request->only('email'));

        return $this->successResponse(null, $message);
    }

    /**
     * Reset the given user's password.
     *
     * @return JsonResponse
     */
    public function resetPassword(Request $request, ResetPasswordAction $action)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $message = $action->execute($request->all());

        return $this->successResponse(null, $message);
    }

    /**
     * Mark the user's email address as verified.
     *
     * @return JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return $this->errorResponse('Invalid verification link.', 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(null, 'Email already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->successResponse(null, 'Email verified successfully');
    }

    /**
     * Resend the email verification notification.
     *
     * @return JsonResponse
     */
    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->errorResponse('Email already verified.', 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->successResponse(null, 'Verification link sent');
    }
}
