<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

#[Backoff(30)]
#[MaxExceptions(3)]
#[Timeout(60)]
#[Tries(5)]
class VerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;
}
