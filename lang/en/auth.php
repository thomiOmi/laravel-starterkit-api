<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | Framework keys below are consumed by Laravel without a namespace, so
    | they must live in the application lang directory, not in a module.
    |
    */

    'failed' => 'Invalid email or password. Please try again.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    // Emails sent by the application.
    'email_verify_subject' => 'Verify Email Address',
    'email_verify_line' => 'Click the button below to verify your email address.',
    'email_verify_action' => 'Verify Email Address',
    'email_verify_footer' => 'If you did not create an account, no further action is required.',
    'password_reset_subject' => 'Reset your password',
    'password_reset_line' => 'You are receiving this email because we received a password reset request for your account.',
    'password_reset_action' => 'Reset Password',
    'password_reset_expire' => 'This password reset link will expire in :count minutes.',
    'password_reset_footer' => 'If you did not request a password reset, no further action is required.',

    // Middleware messages.
    'email_verify_required' => 'Please verify your email address before accessing this resource.',
    'unauthenticated' => 'You must be authenticated to access this resource.',
    'account_banned' => 'This account has been banned. Please contact support.',
    'account_suspended' => 'This account has been suspended. Please contact support.',
    'account_inactive' => 'This account is inactive. Please contact support.',

    // HTTP status titles (RFC 9457 Problem Details).
    'http_unauthorized' => 'Unauthenticated',
    'http_bad_request' => 'Bad Request',
    'http_forbidden' => 'Forbidden',
    'http_not_found' => 'Not Found',
    'http_validation_failed' => 'Validation Failed',
    'http_too_many_requests' => 'Too Many Requests',
    'http_internal_error' => 'Internal Server Error',
    'http_gone' => 'Gone',
    'http_conflict' => 'Conflict',

    // HTTP status detail (fallback for ProblemResponse).
    'validation_failed' => 'The given data was invalid.',
    'invalid_signature' => 'The request signature is invalid or has expired.',
    'access_denied' => 'You are not authorised to perform this action.',
    'not_found_detail' => 'The requested URL does not exist.',
    'rate_limited_detail' => 'You have exceeded the request rate limit. Please try again later.',
    'bad_request_detail' => 'The request could not be understood by the server due to malformed syntax.',
    'conflict_detail' => 'The request conflicts with the current state of the server.',
    'internal_error_detail' => 'An internal server error occurred.',

];
