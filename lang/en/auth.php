<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    // Laravel internal — do not rename
    'failed' => 'Invalid email or password. Please try again.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    // Auth flow
    'register_success' => 'Registration successful.',
    'login_success' => 'Login successful.',
    'logout_success' => 'Logout successful.',
    'password_invalid' => 'The provided password does not match your current password.',
    'password_reset_link_sent' => 'We have emailed your password reset link.',
    'password_reset_success' => 'Your password has been reset.',
    'password_updated' => 'Your password has been updated.',
    'password_expired' => 'Your password has expired. Please update your password.',
    'profile_updated' => 'Your profile has been updated.',
    'device_logout_success' => 'Device logged out successfully.',
    'other_devices_logout_success' => 'Other devices logged out successfully.',
    'social_login_success' => 'Social login successful.',
    'social_denied' => 'You have denied the authorization request.',
    'email_verified' => 'Email verified successfully.',
    'email_verification_sent' => 'A new verification link has been sent to your email address.',
    'email_not_verified' => 'Email Not Verified',
    'email_verify_required' => 'Please verify your email address before accessing this resource.',
    'email_verify_subject' => 'Verify Email Address',
    'email_verify_line' => 'Click the button below to verify your email address.',
    'email_verify_action' => 'Verify Email Address',
    'email_verify_footer' => 'If you did not create an account, no further action is required.',
    'password_reset_subject' => 'Reset your password',
    'password_reset_line' => 'You are receiving this email because we received a password reset request for your account.',
    'password_reset_action' => 'Reset Password',
    'password_reset_expire' => 'This password reset link will expire in :count minutes.',
    'password_reset_footer' => 'If you did not request a password reset, no further action is required.',
    'account_banned' => 'This account has been banned. Please contact support.',
    'account_suspended' => 'This account has been suspended. Please contact support.',
    'account_inactive' => 'This account is inactive. Please contact support.',

    // HTTP status titles (RFC 9457 Problem Details)
    'http_unauthorized' => 'Unauthenticated',
    'unauthenticated' => 'You must be authenticated to access this resource.',
    'http_bad_request' => 'Bad Request',
    'http_forbidden' => 'Forbidden',
    'http_not_found' => 'Not Found',
    'http_validation_failed' => 'Validation Failed',
    'http_too_many_requests' => 'Too Many Requests',
    'http_internal_error' => 'Internal Server Error',
    'http_gone' => 'Gone',

    // HTTP status detail (fallback for ProblemResponse)
    'validation_failed' => 'The given data was invalid.',
    'invalid_signature' => 'The request signature is invalid or has expired.',
    'access_denied' => 'You are not authorised to perform this action.',
    'not_found_detail' => 'The requested URL does not exist.',
    'rate_limited_detail' => 'You have exceeded the request rate limit. Please try again later.',
    'bad_request_detail' => 'The request could not be understood by the server due to malformed syntax.',
    'internal_error_detail' => 'An internal server error occurred.',
];
