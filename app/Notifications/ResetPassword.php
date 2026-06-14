<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Modules\User\Models\User;

class ResetPassword extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    protected function resetUrl(mixed $notifiable): string
    {
        /** @var User $notifiable */
        $frontendUrl = Config::string('app.frontend_url').'/reset-password';

        $params = http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return $frontendUrl.'?'.$params;
    }
}
