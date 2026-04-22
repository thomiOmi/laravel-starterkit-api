<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends BaseVerifyEmail
{
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
