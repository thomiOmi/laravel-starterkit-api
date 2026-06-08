<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\URL;
use Modules\User\Models\User;

/**
 * Custom Verify Email notification to use API routes and support background queuing.
 */
class VerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Ensure notification is only dispatched after the database transaction commits.
     */
    public function __construct()
    {
        $this->afterCommit();
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable  The notifiable model.
     * @return string The verification URL.
     */
    protected function verificationUrl($notifiable): string
    {
        /** @var User $notifiable */
        /** @var int $expire */
        $expire = config('auth.verification.expire', 60);

        return URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes($expire),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
