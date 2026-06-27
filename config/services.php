<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Postmark
    |--------------------------------------------------------------------------
    |
    | Postmark is a transactional email service. Configure your API key
    | here to enable sending emails through Postmark.
    |
    | https://postmarkapp.com
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resend
    |--------------------------------------------------------------------------
    |
    | Resend is an email delivery service. Configure your API key here
    | to enable sending emails through Resend.
    |
    | https://resend.com
    |
    */

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Amazon SES
    |--------------------------------------------------------------------------
    |
    | Amazon Simple Email Service (SES) configuration. You can use SES
    | for sending transactional and marketing emails.
    |
    | https://aws.amazon.com/ses
    |
    */

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack
    |--------------------------------------------------------------------------
    |
    | Slack integration for notifications. Configure the bot user OAuth
    | token and default channel to receive Slack notifications.
    |
    | https://api.slack.com
    |
    */

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google OAuth
    |--------------------------------------------------------------------------
    |
    | Google OAuth 2.0 credentials for social authentication via Socialite.
    | Used to allow users to sign in with their Google account.
    |
    | https://console.cloud.google.com/apis/credentials
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub OAuth
    |--------------------------------------------------------------------------
    |
    | GitHub OAuth App credentials for social authentication via Socialite.
    | Used to allow users to sign in with their GitHub account.
    |
    | https://github.com/settings/developers
    |
    */

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI'),
    ],
];
