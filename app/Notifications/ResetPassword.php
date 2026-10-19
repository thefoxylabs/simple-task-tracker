<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Laravel's password reset email, sent through the queue. The job is
 * encrypted so the reset token isn't readable in the jobs tables.
 */
class ResetPassword extends BaseResetPassword implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function backoff(): array
    {
        return [60, 300, 900];
    }
}
